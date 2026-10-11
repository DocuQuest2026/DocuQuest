import http from 'k6/http';
import { check, group, sleep } from 'k6';

/**
 * Load test for DocuQuest. Run it against the isolated e2e copy of the app, never real data:
 *
 *   cd public && php -d zend_extension=opcache -d opcache.enable_cli=1 -S 127.0.0.1:8001 -t . ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
 *   (with APP_ENV=e2e, after `php artisan migrate:fresh --env=e2e && php artisan db:seed --class=E2eSeeder --env=e2e`)
 *   k6 run tests/load/load-test.js
 *
 * Set BASE_URL to test another address.
 */
const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8001';

export const options = {
    scenarios: {
        // Students and visitors reading public pages and checking a request's status.
        students: {
            executor: 'ramping-vus',
            exec: 'student',
            startVUs: 0,
            stages: [
                { duration: '20s', target: 3 },
                { duration: '40s', target: 3 },
                { duration: '10s', target: 0 },
            ],
        },
        // Students submitting new requests at a steady rate.
        submissions: {
            executor: 'constant-arrival-rate',
            exec: 'submitRequest',
            rate: 1,
            timeUnit: '4s',
            duration: '1m',
            preAllocatedVUs: 2,
            maxVUs: 4,
        },
        // Registrar staff working through the queue.
        staff: {
            executor: 'constant-vus',
            exec: 'registrar',
            vus: 1,
            duration: '1m10s',
        },
    },
    thresholds: {
        http_req_failed: ['rate<0.01'],
        'http_req_duration{kind:page}': ['p(95)<3000'],
        'http_req_duration{kind:action}': ['p(95)<4000'],
        checks: ['rate>0.99'],
    },
};

const csrfToken = (response) => response.html().find('input[name=_token]').first().attr('value');

function submissionForm(token, studentNo) {
    return {
        _token: token,
        student_no: studentNo,
        first_name: 'Load',
        last_name: 'Tester',
        course: 'BS Information Technology',
        enrolment_status: 'alumni',
        email: 'load.test@gmail.com',
        contact_no: '09171234567',
        'documents[transcript_of_records][copies]': '1',
        purpose: 'Load test',
    };
}

export function setup() {
    // A few real requests for the status lookups to find.
    const references = [];

    for (let i = 0; i < 3; i++) {
        const form = http.get(`${BASE_URL}/request`);
        const response = http.post(`${BASE_URL}/request`, submissionForm(csrfToken(form), `2026-9${i}000`));
        const match = response.body.match(/REQ-[A-Z0-9]{8}/);

        if (match) {
            references.push(match[0]);
        }
    }

    return { references };
}

export function student(data) {
    group('browse', () => {
        const home = http.get(`${BASE_URL}/`, { tags: { kind: 'page' } });
        check(home, { 'landing page loads': (r) => r.status === 200 });

        const form = http.get(`${BASE_URL}/request`, { tags: { kind: 'page' } });
        check(form, { 'request form loads': (r) => r.status === 200 && r.body.includes('Estimated ready by') });
    });

    sleep(1);

    group('check status', () => {
        const page = http.get(`${BASE_URL}/request/status`, { tags: { kind: 'page' } });
        const reference = data.references[Math.floor(Math.random() * data.references.length)];

        const lookup = http.post(
            `${BASE_URL}/request/status`,
            { _token: csrfToken(page), reference_no: reference },
            { tags: { kind: 'action' } },
        );

        check(lookup, {
            'status page shows the request': (r) => r.status === 200 && r.body.includes(reference),
            'status page shows the queue place': (r) => r.body.includes('Place in queue'),
        });
    });

    sleep(1);
}

export function submitRequest() {
    const form = http.get(`${BASE_URL}/request`, { tags: { kind: 'page' } });
    const studentNo = `2026-${Math.floor(Math.random() * 90000) + 10000}`;

    const response = http.post(`${BASE_URL}/request`, submissionForm(csrfToken(form), studentNo), { tags: { kind: 'action' } });

    check(response, { 'a reference number is issued': (r) => /REQ-[A-Z0-9]{8}/.test(r.body) });
}

export function registrar() {
    const login = http.get(`${BASE_URL}/staff/login`, { tags: { kind: 'page' } });

    const signedIn = http.post(
        `${BASE_URL}/staff/login`,
        { _token: csrfToken(login), email: 'e2e.staff@gmail.com', password: 'password' },
        { tags: { kind: 'action' } },
    );
    check(signedIn, { 'staff can sign in': (r) => r.status === 200 && r.url.includes('/dashboard') });

    for (const path of ['/dashboard', '/requests', '/requests/cancellations', '/reports']) {
        const page = http.get(`${BASE_URL}${path}`, { tags: { kind: 'page' } });
        check(page, { [`${path} loads`]: (r) => r.status === 200 });
        sleep(1);
    }

    const logoutPage = http.get(`${BASE_URL}/dashboard`, { tags: { kind: 'page' } });
    http.post(`${BASE_URL}/logout`, { _token: csrfToken(logoutPage) }, { tags: { kind: 'action' } });
}
