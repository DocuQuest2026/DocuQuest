# Testing DocuQuest

## What software testing does

Software testing means checking, with a program instead of by hand, that the system behaves the way it should. A test is a small script that does something a user or the system would do, then compares what happened with what was expected.

For example, a test submits a request for a Transcript of Records, then checks that:
- a reference number was created,
- the fee shown is the one set by the admin,
- the request appears in the staff list as Pending, and
- the student can find it on the status page.

If every comparison matches, the test passes. If one does not, the test fails and says exactly which one.

What this gives the project:
- **Finds bugs early.** Problems show up while building, not when a student or the registrar hits them.
- **Protects what already works.** Each new feature (urgent flag, queue, reports) could accidentally break an older one. Rerunning the tests catches that in minutes.
- **Saves time.** Checking every page, role and edge case by hand takes hours. The tests do it again on demand.
- **Documents the expected behavior.** The test names read as a list of what the system is supposed to do.
- **Gives evidence.** The results show how well the system was verified, which supports the capstone defense.

Testing cannot prove the system has no bugs. It only proves that the things being checked work.

## Why we test

DocuQuest handles real student requests, fees and released documents. Automated tests check that the system works, and keeps working, every time we change it. A mistake found by a test costs minutes. A mistake found by a student or the registrar costs trust and rework.

No single tool can check everything, so the project uses four, each covering something the others cannot:

| Tool | What it checks | Question it answers |
| --- | --- | --- |
| Pest | The code behind the pages | "Does the logic give the right result?" |
| Playwright | The real pages in a browser | "Can a person actually use it, on desktop and phone?" |
| Laravel Dusk | The real pages, driven from PHP | "Do the main flows work end to end inside the Laravel project?" |
| k6 | Speed and stability under load | "Does it hold up when many people use it at once?" |

## What each tool does and why it was chosen

### Pest (code tests, 525 tests)

- **What it does:** Runs the code directly with no browser. It checks things like a request getting a reference number, staff flagging a request as urgent, fees being calculated, the queue order, the estimated ready date, only admins editing documents, and the report refusing dates before September 2026.
- **Why:** It is the standard test framework for Laravel, so it fits the project and is the fastest, most precise way to test business rules. Every rule can be tested with its failure cases (wrong input, wrong user, missing permission).

### Playwright (browser tests, 13 checks run on desktop and phone = 18 tests)

- **What it does:** Opens a real browser, then clicks and types like a student or staff member. Public-page checks run at desktop size and at phone size.
- **Why:** Code tests cannot see a broken button, a hidden form field or a layout that fails on a phone. Playwright is fast, works across browsers, and shows screenshots and a report when something fails. Many students will use the system on phones, so the phone run matters.

### Laravel Dusk (browser tests, 6 tests)

- **What it does:** Also drives a real browser, but the tests are written in PHP and live in the Laravel project (`tests/Browser`).
- **Why:** It is the official Laravel browser tool. It gives a second, independent check of the most important flows, written in the same language as the rest of the project, so any developer on the team can read and maintain it.

### k6 (load test)

- **What it does:** Simulates many users at once: students browsing and checking status, students submitting requests, and staff working through the queue. It reports how many requests failed and how long pages took.
- **Why:** The registrar's busiest periods (enrolment, graduation, deadlines) bring many users together. A system that works for one user can still be slow or fail for fifty. k6 is built for this and shows the problem before real users find it.
- **Note:** The speed targets were not met on a local development computer, because it uses a single-threaded test server and a remote database. The numbers should be measured again on the real hosting before being quoted.

## What the tests actually check

### Playwright

Public pages:
1. The landing page offers every document and a request button.
2. The request form lists the documents with their fees and shows the estimated ready date once one is chosen.
3. Submitting an empty request form is stopped by validation.
4. The status page rejects an unknown reference number.
5. Staff pages require signing in.

Full journey (one request followed from start to finish):
6. A student submits a request and gets a reference number.
7. The student follows the request on the status page, with its place in the queue and its estimated ready date.
8. Staff approve the request, and the status page updates by itself without a reload (the auto-refresh).
9. Staff release the document and mark it claimed.
10. The student sees the document as claimed.
11. The report counts the request and downloads as a PDF.

Admin:
12. An admin changes a document fee, and the request form shows the new fee.
13. Staff cannot open the admin pages.

### Dusk

1. The landing page offers every document and a request button.
2. Choosing a document on the request form shows the fee and the estimated ready date.
3. The status page rejects an unknown reference number.
4. A student submits a request, then finds it on the status page with its queue place.
5. Staff can sign in, open the request list and open the reports page.
6. Staff are refused the admin Documents page.

### Pest

The 525 tests cover every feature built so far: requests and their statuses, cancellations, archiving, history by emailed code, the urgent flag, queue positions, estimated processing time, admin-managed documents, the status page auto-refresh, the reports page and PDF, permissions for staff and admins, and the rate limits on the public forms.

### How to read a result

- **Passed** means the behavior still works as described above.
- **Failed** means something changed that should not have. The report shows which check failed, and for browser tests a screenshot of the page at that moment.
- A passing result only proves the checks listed here. It does not prove there are no bugs, so the app should still be tried by hand before it is presented.

## Safety

The browser and load tests never touch real data. They run a separate copy of the app with its own database schemas (`e2e`, `dusk`, `test`), set up in `.env.e2e` and `.env.dusk.local` (not committed to git).

## How to run them

Open a terminal in the project folder. Run one type at a time, never two together, because they share the same database.

### Playwright

```
npx playwright test
```

- Watch the browser: `npx playwright test --headed`
- Interactive window: `npx playwright test --ui`
- See the report afterwards: `npx playwright show-report`

### Dusk

```
vendor/bin/pest tests/Browser
```

- Run one test: `vendor/bin/pest tests/Browser --filter="name of test"`
- Watch the browser (PowerShell): `$env:DUSK_HEADLESS_DISABLED='true'; vendor/bin/pest tests/Browser`
- Do not use `php artisan dusk`.

### Pest

```
php artisan test --compact
```

Takes about 22 minutes because the tests use the remote database.

### k6

Needs the e2e copy of the app running on port 8001 (see the comment at the top of `tests/load/load-test.js`).

```
npm run test:load
```

## Latest results (October 11, 2026)

- Pest: 525 of 525 passed
- Playwright: 18 of 18 passed
- Dusk: 6 of 6 passed
- k6: no errors, but speed targets not met on the local setup (see the note above)
