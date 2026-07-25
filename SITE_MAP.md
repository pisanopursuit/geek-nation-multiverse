# Geek Nation Multiverse — Site Map

## Purpose

This document defines the information architecture for Geek Nation Multiverse during Phase 3: Platform Integration.

Geek Nation Multiverse is a platform connecting fans, creators, collectors, educators, artists, and vendors through shared communities, events, commerce, and collaboration.

The platform should feel like walking through the halls of a major Comic-Con: huge, busy, colorful, exciting, discovery-driven, and community-first.

Every major page should avoid dead ends and guide users toward related content across the platform.

---

# 1. Primary Public Navigation

1. Home
2. Explore
3. Universes
4. Booths
5. Panels & Events
6. Artist Alley
7. Multiverse Academy
8. Collectors Marketplace
9. About

Secondary public actions:

- Global Search
- Sign In
- Register
- Cart
- Member Menu

---

# 2. Home

## Route

`/`

## Purpose

The homepage is the front entrance to the online convention.

## Sections

### Hero

- Platform identity
- Primary call to action
- Secondary call to action
- Featured announcement or campaign

### Featured Universe

- Featured universe cards
- Trending universes
- Active communities
- Link: `Explore Universes →`

### Featured Artist

- Featured artists
- Artists open for commissions
- Artists appearing at upcoming events
- Link: `Explore Artists →`

### Featured Booth

- Featured booths
- New booths
- Popular products
- Convention exclusives
- Link: `Explore Booths →`

### Trending Collectible

- Trending listings
- Rare finds
- New listings
- Wanted items
- Link: `Explore Collectibles →`

### Panel Starting Soon

- Live panels
- Upcoming panels
- Events beginning soon
- Link: `Explore Panels & Events →`

### Course Spotlight

- Featured courses
- Upcoming live classes
- Recently added courses
- Link: `Explore the Academy →`

### Community Activity

- New members
- Recent community posts
- New universe activity
- Recent follows and joins
- Link: `Explore Community →`

### Optional Supporting Sections

- Featured companies
- Featured brands
- New members
- Latest products
- Platform announcements

---

# 3. Explore

## Route

`/explore`

## Purpose

The central discovery hub for the entire platform.

## Sections

- Trending now
- Featured universes
- Featured artists
- Featured booths
- Upcoming panels and events
- Popular courses
- Rare collectibles
- New companies and brands
- Recently active members
- Personalized recommendations

## Filters

- Content type
- Category
- Universe
- Location
- Online / in-person
- Newest
- Most active
- Featured
- Trending

---

# 4. Universes

Universes are fandom-based communities.

## Directory

Route: `/universes`

Sections:

- Featured universes
- Trending universes
- New universes
- Most active universes
- Browse by genre
- Browse by category

## Universe Detail

Route: `/universes/{slug}`

Sections:

- Universe hero and description
- Join / leave action
- Member count
- Activity feed
- Community billboard
- Community chat
- Related artists
- Related booths
- Related panels and events
- Related courses
- Related collectibles
- Related brands
- Related companies
- Community moderators
- Suggested universes

## Universe Member Pages

- Members
- Discussions
- Billboard
- Chat
- Events
- Artists
- Booths
- Courses
- Collectibles
- Media
- Rules

## Universe Management

- Overview
- Profile
- Membership
- Moderators
- Content
- Events
- Relationships
- Settings
- Reports

---

# 5. Booths

Booths represent vendors, creators, publishers, studios, and exhibitors.

## Directory

Route: `/booths`

Sections:

- Featured booths
- New booths
- Popular booths
- Convention exclusives
- Browse by category
- Browse by universe
- Browse by company
- Browse by brand

## Booth Detail

Route: `/booths/{slug}`

Sections:

- Booth hero
- Booth profile
- Owner / team
- Product catalog
- Featured products
- Gallery
- Downloads
- Upcoming appearances
- Related events
- Related artists
- Related universes
- Related brands
- Related companies
- Recommended booths

## Product Detail

Route: `/booths/{booth-slug}/products/{product-slug}`

Sections:

- Product details
- Gallery
- Price
- Availability
- Seller information
- Related products
- Related universe
- Related artist
- Related collectibles
- Recommended booths

## Booth Owner Management

- Booth overview
- Profile
- Products
- Inventory
- Orders
- Gallery
- Downloads
- Team
- Events
- Relationships
- Settings
- Analytics

---

# 6. Panels & Events

## Directory

Route: `/events`

Sections:

- Starting soon
- Happening today
- Upcoming this week
- Virtual events
- In-person events
- Hybrid events
- Panels
- Workshops
- Signings
- Tournaments
- Meetups

## Event Detail

Route: `/events/{slug}`

Sections:

- Event hero
- Date and time
- Time zone
- Location
- Registration / RSVP
- Schedule
- Speakers
- Attendees
- Participating booths
- Featured artists
- Related universes
- Related courses
- Sponsors
- Related companies and brands
- Similar events

## Organizer Management

- Overview
- Event details
- Schedule
- Speakers
- Attendees
- Registration
- Check-in
- Capacity
- Waitlist
- Relationships
- Communications
- Settings
- Analytics

---

# 7. Artist Alley

## Directory

Route: `/artists`

Sections:

- Featured artists
- New artists
- Artists open for commissions
- Artists appearing at upcoming events
- Browse by medium
- Browse by universe
- Most followed artists

## Artist Detail

Route: `/artists/{slug}`

Sections:

- Artist hero
- Biography
- Specialties
- Portfolio
- Commission services
- Commission availability
- Upcoming appearances
- Related booths
- Related events
- Related universes
- Courses taught
- Collectibles created
- Followers
- Recommended artists

## Artist Management

- Overview
- Profile
- Portfolio
- Commission services
- Commission requests
- Followers
- Events
- Courses
- Booth relationships
- Settings
- Analytics

---

# 8. Multiverse Academy

## Directory

Route: `/academy`

Sections:

- Featured courses
- New courses
- Beginner courses
- Live classes
- Workshops
- Trending courses
- Browse by category
- Browse by level
- Browse by instructor

## Course Detail

Route: `/academy/courses/{slug}`

Sections:

- Course hero
- Description
- Instructor
- Course level
- Lessons
- Preview lessons
- Enrollment
- Capacity
- Schedule
- Related universe
- Related artist
- Related event
- Similar courses

## Lesson Detail

Route: `/academy/courses/{course-slug}/lessons/{lesson-slug}`

Supported lesson types:

- Video
- Article
- Download
- Quiz
- Assignment
- Live session

## Student Area

- My courses
- Course progress
- Completed lessons
- Assignments
- Live sessions
- Saved courses

## Instructor Management

- Overview
- Course profile
- Lessons
- Lesson order
- Instructors and assistants
- Students
- Enrollment
- Progress
- Assignments
- Settings
- Analytics

---

# 9. Collectors Marketplace

## Directory

Route: `/collectors-marketplace`

Sections:

- Trending collectibles
- Rare finds
- Recently listed
- For sale
- For trade
- Wanted
- Showcase
- Featured collectors
- Browse by category
- Browse by universe

## Collectible Detail

Route: `/collectors-marketplace/items/{slug}`

Sections:

- Item gallery
- Description
- Condition
- Category
- Franchise / universe
- Listing type
- Price
- Quantity
- Seller / collector
- Offer actions
- Favorite action
- Related collectibles
- Related artist
- Related booth
- Related universe
- Similar listings

## Collector Profile

Route: `/collectors/{slug}`

Sections:

- Collector hero
- Biography
- Personal collection
- Active listings
- Wanted items
- Showcase items
- Favorites
- Trade history
- Related universes
- Recommended collectors

## Collector Management

- Overview
- Profile
- Collection
- Listings
- Offers received
- Offers sent
- Favorites
- Wanted items
- Showcase
- Settings
- Analytics

---

# 10. About

## Main About Page

Route: `/about`

Sections:

- What Geek Nation Multiverse is
- Who it is for
- Mission
- Platform story
- How the ecosystem works
- Community values
- Contact
- Frequently asked questions

## Supporting Pages

- Community guidelines
- Terms of service
- Privacy policy
- Accessibility
- Help center
- Contact
- Press
- Partnerships

---

# 11. Authentication

## Public Authentication Pages

- Sign in
- Register
- Forgot password
- Reset password
- Email verification
- Access pending
- Account suspended
- Logout confirmation

---

# 12. Member Dashboard

## Route

`/dashboard`

## Purpose

A single role-aware command center.

## Universal Widgets

- Notifications
- Messages
- Upcoming panels and events
- Recent activity
- Favorites
- Quick actions
- Recently viewed
- Search
- Recommended content

## Fan Widgets

- My universes
- Favorite artists
- Favorite booths
- Saved events
- Recommended panels
- Community activity

## Artist Widgets

- Commission requests
- Portfolio activity
- Followers
- Upcoming appearances
- Artist profile status
- Quick upload

## Booth Owner Widgets

- Recent orders
- Inventory alerts
- Booth views
- Product performance
- Upcoming events
- Customer activity

## Instructor Widgets

- Active courses
- Student enrollments
- Course progress
- Upcoming live sessions
- Assignments
- Course reviews

## Event Organizer Widgets

- Upcoming events
- Registrations
- Capacity
- Speakers
- Check-in status
- Schedule alerts

## Collector Widgets

- Collection summary
- Active listings
- Offers
- Trade requests
- Favorites
- Wanted items

## Company / Brand Admin Widgets

- Managed companies
- Managed brands
- Booth performance
- Sponsored events
- Team management
- Content status

## Site Administrator Widgets

- Platform health
- Pending approvals
- New users
- Reports
- Moderation
- Demo tools
- Developer Center
- Import Center

---

# 13. Member Account Area

## Profile

- Public profile
- Edit profile
- Avatar
- Banner
- Biography
- Social links
- Interests
- Universes
- Activity
- Privacy
- Account settings

## Member Menu

- Dashboard
- My profile
- My universes
- My booths
- My events
- My artist profile
- My courses
- My collection
- Favorites
- Messages
- Notifications
- Account settings
- Sign out

---

# 14. Companies

## Directory

Route: `/companies`

## Company Detail

Route: `/companies/{slug}`

Sections:

- Company profile
- Brands
- Booths
- Events
- Artists
- Courses
- Universes
- Team
- Related companies

## Company Management

- Overview
- Profile
- Brands
- Team
- Booths
- Events
- Relationships
- Settings

---

# 15. Brands

## Directory

Route: `/brands`

## Brand Detail

Route: `/brands/{slug}`

Sections:

- Brand profile
- Parent company
- Products
- Booths
- Events
- Artists
- Courses
- Universes
- Related brands

## Brand Management

- Overview
- Profile
- Team
- Booths
- Events
- Relationships
- Settings

---

# 16. Global Search

## Route

`/search`

## Search Types

- Users
- Companies
- Brands
- Universes
- Booths
- Products
- Panels and events
- Artists
- Courses
- Collectibles

## Filters

- Content type
- Category
- Universe
- Location
- Date
- Online / in-person
- Newest
- Featured
- Trending
- Most active

## Search Result Requirements

Every result should include:

- Content type
- Title
- Image
- Short description
- Relevant metadata
- Direct link
- Related universe where applicable

---

# 17. Cart and Demo Checkout

## Cart

Route: `/cart`

- Products
- Quantities
- Seller / booth
- Estimated total
- Remove item
- Continue exploring
- Demo checkout

## Demo Checkout

Used until payments are implemented.

- Contact information
- Shipping details
- Order review
- Simulated confirmation
- Order record creation

Real payments remain the final platform phase.

---

# 18. Administration

Administration should use a separate admin navigation or sidebar.

## Admin Home

Route: `/admin`

Widgets:

- Platform health
- Pending approvals
- New users
- Recent reports
- Content totals
- System alerts

## Admin Sections

- Users
- Profiles
- Companies
- Brands
- Universes
- Booths
- Products
- Orders
- Panels and events
- Artists
- Academy
- Collectors
- Collectibles
- Reports
- Moderation
- Site settings
- Developer Center
- Import Center
- Audit logs

---

# 19. Developer Center

## Route

`/developer`

Sections:

- Demo data generation
- Demo data cleanup
- Diagnostics
- Database status
- Module status
- Permission testing
- Error logs
- Email preview
- Feature flags
- System information

---

# 20. Import Center

## Route

`/admin/import`

Import types:

- Users
- Companies
- Brands
- Universes
- Booths
- Products
- Events
- Artists
- Courses
- Collectibles

Import process:

1. Upload file
2. Map columns
3. Validate
4. Preview
5. Import
6. Review results
7. Download error report

---

# 21. Shared Page Structure

Every major public page should follow a common pattern.

1. Header
2. Hero or page title
3. Primary content
4. Related platform content
5. Explore link
6. Footer

## Standard Section Header

Each major homepage and directory section uses:

- Section title
- Optional description
- Explore link

Examples:

- `Explore Universes →`
- `Explore Artists →`
- `Explore Booths →`
- `Explore Panels & Events →`
- `Explore the Academy →`
- `Explore Collectibles →`
- `Explore Community →`

---

# 22. Cross-Module Relationships

## Universe Connections

A universe can connect to:

- Members
- Artists
- Booths
- Events
- Courses
- Collectibles
- Brands
- Companies

## Artist Connections

An artist can connect to:

- Booths
- Events
- Universes
- Courses
- Collectibles
- Companies
- Brands

## Booth Connections

A booth can connect to:

- Products
- Owner
- Team
- Artists
- Events
- Universes
- Brands
- Companies

## Event Connections

An event can connect to:

- Speakers
- Attendees
- Artists
- Booths
- Universes
- Courses
- Companies
- Brands

## Course Connections

A course can connect to:

- Instructor
- Students
- Universe
- Artist
- Event
- Company
- Brand

## Collectible Connections

A collectible can connect to:

- Collector
- Universe
- Artist
- Booth
- Brand
- Company

---

# 23. Core User Journeys

## Fan Journey

Home → Explore → Universe → Join → Event → Artist → Booth → Favorite

## Artist Journey

Register → Create artist profile → Upload portfolio → Add commission service → Join universe → Attend event → Connect booth

## Vendor Journey

Register → Create company or brand → Create booth → Add products → Join event → Link universe → Receive demo order

## Collector Journey

Register → Create collector profile → Add collection item → Create listing → Receive offer → Explore related universe

## Educator Journey

Register → Create instructor profile → Create course → Add lessons → Link universe → Enroll students → Host live session

## Event Organizer Journey

Register → Create event → Add schedule → Add speakers → Link booths and artists → Open registration → Manage attendees

## Administrator Journey

Sign in → Review approvals → Check diagnostics → Manage content → Review reports → Use Developer Center

---

# 24. Design Principles Applied to the Site Map

## Discovery First

Every directory and detail page should expose related content.

## No Dead Ends

Every major page must include at least one meaningful next step.

## Convention Before Marketplace

Commerce supports the ecosystem but does not define it.

## Community Before Commerce

Universes, events, artists, and member activity remain central.

## Explore Everywhere

Every major section includes a clear exploration link.

## One Platform

Navigation, cards, forms, statuses, and page structures should remain consistent across modules.

## Comic-Con Energy

The interface should remain bold, colorful, active, and content-rich without becoming confusing.

## Role-Aware Experience

The dashboard and member tools adapt based on each member’s roles and relationships.

---

# 25. Phase 3 Implementation Order

1. Shared navigation architecture
2. Shared page shell
3. Shared section-header component
4. Shared card system
5. Homepage integration
6. Explore hub
7. Role-aware dashboard
8. Global search
9. Cross-module relationship blocks
10. Admin navigation
11. Platform-wide QA

---

# 26. Definition of Done

Phase 3 Platform Integration is complete when:

- The public navigation matches the approved structure.
- The homepage exposes all major platform pillars.
- Every homepage section includes an Explore link.
- Users receive a role-aware dashboard.
- Search spans all major content types.
- Shared components are used consistently.
- Public, member, and admin navigation are separated.
- Major detail pages include related cross-module content.
- No major page is a dead end.
- The site feels like one cohesive online convention.
