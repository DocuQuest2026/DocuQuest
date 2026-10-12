<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('staff and admins can upload a profile photo', function (string $state) {
    $user = User::factory()->{$state}()->create();

    $this->actingAs($user)
        ->post(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('me.jpg', 300, 300)])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->profile_photo_path)->not->toBeNull();
    Storage::disk('local')->assertExists($user->profile_photo_path);
    expect($user->profilePhotoUrl())->toContain('/profile/photo');
})->with(['staff', 'admin']);

test('uploading a new photo replaces and deletes the old one', function () {
    $user = User::factory()->staff()->create();

    $this->actingAs($user)->post(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('one.png')]);
    $oldPath = $user->refresh()->profile_photo_path;

    $this->actingAs($user)->post(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('two.png')]);

    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($user->refresh()->profile_photo_path);
});

test('the photo must be a small JPG, PNG or WebP image', function (UploadedFile $file) {
    $user = User::factory()->staff()->create();

    $this->actingAs($user)
        ->post(route('profile.photo.update'), ['photo' => $file])
        ->assertSessionHasErrors('photo');

    expect($user->refresh()->profile_photo_path)->toBeNull();
})->with([
    'a document' => fn () => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
    'an image over 2 MB' => fn () => UploadedFile::fake()->image('big.jpg')->size(3000),
    'a gif' => fn () => UploadedFile::fake()->image('anim.gif'),
]);

test('a photo can be removed', function () {
    $user = User::factory()->staff()->create();
    $this->actingAs($user)->post(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('me.jpg')]);
    $path = $user->refresh()->profile_photo_path;

    $this->actingAs($user)
        ->delete(route('profile.photo.destroy'))
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->profile_photo_path)->toBeNull();
    Storage::disk('local')->assertMissing($path);
});

test('users can view their own photo, and get a 404 when they have none', function () {
    $user = User::factory()->staff()->create();

    $this->actingAs($user)->get(route('profile.photo.show'))->assertNotFound();

    $this->actingAs($user)->post(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('me.jpg')]);

    $this->actingAs($user)->get(route('profile.photo.show'))->assertOk();
});

test('guests and students can not upload a photo', function () {
    $file = UploadedFile::fake()->image('me.jpg');

    $this->post(route('profile.photo.update'), ['photo' => $file])->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->post(route('profile.photo.update'), ['photo' => $file])
        ->assertForbidden();
});

test('the profile page offers the photo form to staff but not to students', function () {
    $this->actingAs(User::factory()->staff()->create())->get(route('profile.edit'))->assertOk()->assertSee('Profile Photo');
    $this->actingAs(User::factory()->create())->get(route('profile.edit'))->assertOk()->assertDontSee('Profile Photo');
});
