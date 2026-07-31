# API Tested Endpoints

This document tracks which endpoints in the **Entouche API** Postman collection have automated test scripts defined. Use it to monitor test coverage, identify gaps, and guide the team toward full API test coverage.

---

## Coverage Summary

| Metric | Count |
|--------|-------|
| Total Requests Scanned | 103 |
| Requests with Tests | 1 |
| Requests without Tests | 102 |
| Coverage | ~1% |

---

## Tested Endpoints

### Authentication

| Name | Method | URL | Test Description |
|------|--------|-----|-----------------|
| Login | POST | `{{base_url}}/api/v1/auth/login` | Checks status 200 and sets `access_token` collection variable from response |

---

## Untested Endpoints

The following folders currently have **no test scripts** defined. Use the checkboxes below to track coverage progress as tests are added.

- [ ] **Adjustments** (9 requests)
- [ ] **Audit Logs** (2 requests)
- [ ] **Categories** (6 requests)
- [ ] **Imports** (4 requests)
- [ ] **Items** (10 requests)
- [ ] **Locations** (6 requests)
- [ ] **Receipts** (8 requests)
- [ ] **Reports** (4 requests)
- [ ] **Roles & Permissions** (3 requests)
- [ ] **Settings** (3 requests)
- [ ] **Stock Counts** (9 requests)
- [ ] **Suppliers** (6 requests)
- [ ] **Transactions** (2 requests)
- [ ] **Transfers** (10 requests)
- [ ] **Units of Measure** (6 requests)
- [ ] **Users** (8 requests)
- [ ] **Warehouses** (8 requests)

---

> 💡 **Tip for the team:** Adding tests to your Postman requests helps catch regressions early and ensures your API behaves as expected. Start with status code checks, then validate response structure and key fields. Every tested endpoint is a step toward a more reliable API!
