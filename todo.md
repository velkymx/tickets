# Project Issues Tracker

## Code Review Summary

| Severity | Count |
|----------|-------|
| CRITICAL | 6 |
| HIGH | 6 |
| MEDIUM | 10+ |
| LOW | 7+ |

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

- [x] **Missing Form Request Classes** ✅
  - Fixed: StoreTicketRequest, UpdateTicketRequest, EstimateTicketRequest

- [x] **No Authorization Policies** ✅
  - Fixed: TicketPolicy, MilestonePolicy

- [x] **Missing Pagination** ✅
  - Fixed: board(), index(), API endpoints now paginated

- [x] **Missing $casts on Models** ✅
  - Fixed: Added to Ticket, User, Note models

- [x] **Magic Numbers** ✅
  - Fixed: Status::closedStatusIds() method

---

## MEDIUM Priority Issues

### Security

- [x] **API Token Uses Plain SHA256** - Token hashing uses SHA256
- [x] **Session Not Encrypted** ✅
  - Fixed: encrypt=true
- [x] **Missing SameSite Cookie Attribute** ✅
  - Fixed: same_site='lax'

### Performance

- [ ] **Missing Database Indexes** - Check migrations

### Code Quality

- [ ] **Inconsistent Relationship Naming** - user vs assignee
- [ ] **Sprint Model Empty** - May be dead code
- [ ] **Watcher Model May Be Duplicate** - TicketUserWatcher vs Watcher

---

## LOW Priority Issues

- [x] **Importer.php in Models Directory** ✅
  - Fixed: Moved to Services
- [x] **Wrong Import Path in Importer.php** ✅
  - Fixed: Proper namespace imports
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
- Session encryption and SameSite cookies
- bcrypt rounds 10->12
- Health check endpoints in maintenance mode
- Importer moved to Services
