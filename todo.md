# Project Issues Tracker

## Code Review Summary

| Severity | Count |
|----------|-------|
| CRITICAL | 0 |
| HIGH | 3 |
| MEDIUM | 10 |
| LOW | 8 |

---

## CRITICAL Issues - ALL FIXED ✅

- [x] Reference Assignment Bug
- [x] Release.owner() Wrong Relationship
- [x] Milestone.owner()/scrummaster() Wrong Relationship
- [x] Note.created_at in Fillable
- [x] ReleaseController Empty String Dates
- [x] Project.completion Logic Inverted
- [x] N+1 in UsersController::show()
- [x] UsersController Mass Assignment
- [x] ReleaseTicket Migration
- [x] API Token Session Hijacking
- [x] TrustProxies Configuration

---

## HIGH Priority Issues

- [ ] **Missing Form Request Classes**
  - Inline validation throughout
  - Fix: Create `StoreTicketRequest`, `UpdateTicketRequest`, etc.

- [ ] **No Authorization Policies**
  - All controllers allow any authenticated user to modify any resource
  - Fix: Create Policy classes

- [x] **Missing Pagination** ✅
  - Fixed: board(), index(), API endpoints now paginated

- [x] **Missing $casts on Models** ✅
  - Fixed: Added to Ticket, User, Note models

- [x] **Magic Numbers** ✅
  - Fixed: Status::closedStatusIds() method

---

## MEDIUM Priority Issues

### Security

- [ ] **API Token Uses Plain SHA256** - Consider bcrypt for tokens
- [ ] **Session Not Encrypted** - session.php: encrypt=false
- [ ] **Missing SameSite Cookie Attribute** - Add 'lax' or 'strict'

### Performance

- [ ] **Missing Database Indexes** - Check migrations

### Code Quality

- [ ] **Inconsistent Relationship Naming** - user vs assignee
- [ ] **Sprint Model Empty** - May be dead code
- [ ] **Watcher Model May Be Duplicate** - TicketUserWatcher vs Watcher

---

## LOW Priority Issues

- [ ] **Importer.php in Models Directory** - Should be in Services
- [ ] **Wrong Import Path in Importer.php**
- [ ] **Sprint Model Wrong Table Name**
- [ ] **Inconsistent Indentation** - Mix of 2 and 4 spaces
- [ ] **Comments in Production Code**

---

## Completed Fixes

- Reference assignment bug
- File upload security
- Mass assignment in TicketsController
- N+1 queries in home() and board()
- strtotime on null dates
- Empty string comparison
- Division by zero
- $sp undefined variable
- Cache lookups()
- Remove jQuery, use HTML5 date inputs
- Lazy load Quill editor
- Split CSS into chunks
- Remove Tailwind CSS
- Model relationship fixes (Release, Milestone, Note)
- ReleaseController dates (null vs empty string)
- Project completion logic inversion
- UsersController N+1 and validation
- Pagination on board() and API
- $casts on Ticket, User models
- Status::closedStatusIds() method
- TrustProxies env configuration
- API token stateless auth
- ReleaseTicket migration
