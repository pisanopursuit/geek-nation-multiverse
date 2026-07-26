# Geek Nation Multiverse — Data Model

This document is the entity-relationship reference for the platform. It did not exist before this
was written — cross-check it against `database/*.sql` if a table has changed shape since.

There is no `individuals` table. A person is a row in `users`; every role-specific concept
(company staff, brand staff, booth owner, artist, collector, instructor, event attendee) is a
table that points back to `users.id`, not a parallel identity system.

## How to read the diagrams

Crow's-foot notation, standard Mermaid ER conventions:

| Symbol | Meaning |
|---|---|
| `\|\|` | exactly one |
| `\|o` | zero or one (nullable FK on that side) |
| `o{` | zero or many |
| `\|{` | one or many |

So `COMPANIES \|o--o{ BOOTHS` reads: *each booth belongs to zero-or-one company, each company has
zero-or-many booths* — the nullable `booths.company_id` column. Junction tables (`company_members`,
`booth_team_members`, etc.) are drawn as their own boxes with a `\|\|--o{` line in from each side they
join, matching how the schema actually enforces them (two FK columns + a composite/unique key),
rather than a single many-to-many arrow.

Each section below is one module's slice of the schema, kept separate for readability. `users` is
repeated in every diagram as the anchor point — it is the same table throughout.

---

## 1. Organizations & Commerce — Companies, Brands, Booths

The core hierarchy: a **brand** always belongs to exactly one **company**; a **booth** is always
owned by a **user** and may *optionally* be attached to a company, a brand, both, or neither.

```mermaid
erDiagram
    USERS {
        bigint id PK
        varchar username
        enum role "fan|creator|vendor|admin"
        enum status
    }
    COMPANIES {
        bigint id PK
        varchar name
        enum status "draft|pending|approved|rejected|suspended"
        bigint submitted_by FK
    }
    COMPANY_MEMBERS {
        bigint company_id FK
        bigint user_id FK
        enum relationship_type "employee|founder|owner|..."
        enum company_role "pending_owner|owner|company_admin|member|fan"
    }
    BRANDS {
        bigint id PK
        bigint company_id FK
        varchar name
        enum status
    }
    BRAND_MEMBERS {
        bigint brand_id FK
        bigint user_id FK
        enum brand_role "pending_manager|manager|member"
    }
    BOOTHS {
        bigint id PK
        bigint owner_user_id FK
        bigint company_id FK "nullable"
        bigint brand_id FK "nullable"
        enum status
    }
    BOOTH_TEAM_MEMBERS {
        bigint booth_id FK
        bigint user_id FK
        enum role "manager|staff|artist|moderator"
    }
    BOOTH_PRODUCTS {
        bigint id PK
        bigint booth_id FK
        varchar name
        decimal price
    }
    BOOTH_ORDERS {
        bigint id PK
        bigint booth_id FK
        bigint customer_user_id FK "nullable"
        enum order_status
        enum payment_status
    }
    BOOTH_ORDER_ITEMS {
        bigint order_id FK
        bigint product_id FK
        int quantity
    }

    USERS ||--o{ COMPANIES : submits
    USERS ||--o{ COMPANY_MEMBERS : "affiliated via"
    COMPANIES ||--o{ COMPANY_MEMBERS : has
    COMPANIES ||--o{ BRANDS : owns
    USERS ||--o{ BRAND_MEMBERS : "affiliated via"
    BRANDS ||--o{ BRAND_MEMBERS : has
    USERS ||--o{ BOOTHS : owns
    COMPANIES |o--o{ BOOTHS : "optionally runs"
    BRANDS |o--o{ BOOTHS : "optionally runs"
    USERS ||--o{ BOOTH_TEAM_MEMBERS : staffs
    BOOTHS ||--o{ BOOTH_TEAM_MEMBERS : has
    BOOTHS ||--o{ BOOTH_PRODUCTS : sells
    BOOTHS ||--o{ BOOTH_ORDERS : receives
    USERS |o--o{ BOOTH_ORDERS : places
    BOOTH_ORDERS ||--o{ BOOTH_ORDER_ITEMS : contains
    BOOTH_PRODUCTS ||--o{ BOOTH_ORDER_ITEMS : "ordered as"
```

Companies and brands share the same submission/approval shape: `status`, `submitted_by`,
`reviewed_by`, plus a `*_approval_history` audit table (omitted above for space — see
`database/companies.sql` and `database/brands-imports.sql`).

---

## 2. Universes & Community

Universes are a shared taxonomy/social layer, not owned by any company or brand. They self-nest
via `parent_id`, and both booths and users can tag into them independently.

```mermaid
erDiagram
    USERS {
        bigint id PK
    }
    BOOTHS {
        bigint id PK
    }
    UNIVERSES {
        bigint id PK
        bigint parent_id FK "nullable, self-referencing"
        varchar name
        varchar slug
    }
    USER_UNIVERSES {
        bigint user_id FK
        bigint universe_id FK
    }
    UNIVERSE_MODERATORS {
        bigint universe_id FK
        bigint user_id FK
        enum role "owner|moderator"
    }
    BOOTH_UNIVERSES {
        bigint booth_id FK
        bigint universe_id FK
    }
    UNIVERSE_POSTS {
        bigint id PK
        bigint universe_id FK
        bigint user_id FK "nullable"
        bigint parent_post_id FK "nullable, self-referencing"
        text body
    }
    UNIVERSE_CHAT_MESSAGES {
        bigint id PK
        bigint universe_id FK
        bigint user_id FK "nullable"
        varchar message
    }

    UNIVERSES ||--o{ UNIVERSES : "parents"
    USERS ||--o{ USER_UNIVERSES : favorites
    UNIVERSES ||--o{ USER_UNIVERSES : "favorited by"
    USERS ||--o{ UNIVERSE_MODERATORS : moderates
    UNIVERSES ||--o{ UNIVERSE_MODERATORS : has
    BOOTHS ||--o{ BOOTH_UNIVERSES : tagged
    UNIVERSES ||--o{ BOOTH_UNIVERSES : tags
    UNIVERSES ||--o{ UNIVERSE_POSTS : contains
    USERS |o--o{ UNIVERSE_POSTS : authors
    UNIVERSE_POSTS ||--o{ UNIVERSE_POSTS : "replies to"
    UNIVERSES ||--o{ UNIVERSE_CHAT_MESSAGES : contains
    USERS |o--o{ UNIVERSE_CHAT_MESSAGES : sends
```

---

## 3. Events — the cross-cutting glue

Events are the one entity that references companies, brands, booths, *and* universes — but through
a single **polymorphic** join table (`event_relationships.entity_type` + `entity_id`) rather than
four separate FK columns. There is no database-level foreign key on `entity_id`; the application
layer resolves it based on `entity_type`.

```mermaid
erDiagram
    USERS {
        bigint id PK
    }
    EVENTS {
        bigint id PK
        bigint owner_user_id FK
        varchar title
        enum status
        enum format "virtual|physical|hybrid"
    }
    EVENT_RELATIONSHIPS {
        bigint event_id FK
        enum entity_type "company|brand|booth|universe"
        bigint entity_id "polymorphic, no FK constraint"
    }
    EVENT_ATTENDEES {
        bigint event_id FK
        bigint user_id FK
        enum attendee_status
    }
    EVENT_SPEAKERS {
        bigint id PK
        bigint event_id FK
        bigint user_id FK "nullable"
        varchar name
    }

    USERS ||--o{ EVENTS : owns
    EVENTS ||--o{ EVENT_RELATIONSHIPS : links
    EVENTS ||--o{ EVENT_ATTENDEES : has
    USERS ||--o{ EVENT_ATTENDEES : registers
    EVENTS ||--o{ EVENT_SPEAKERS : features
    USERS |o--o{ EVENT_SPEAKERS : "may be"
```

---

## 4. Artist Alley — the independent-creator storefront

An artist profile is a 1:1 extension of a user, parallel to (not part of) the company/brand/booth
chain — a solo creator never needs a company record to sell commissions.

```mermaid
erDiagram
    USERS {
        bigint id PK
    }
    ARTIST_PROFILES {
        bigint id PK
        bigint user_id FK "unique"
        varchar artist_name
        enum status
        enum commission_status "open|closed|waitlist"
    }
    ARTIST_PORTFOLIO_ITEMS {
        bigint id PK
        bigint artist_id FK
        varchar title
        enum item_type
    }
    ARTIST_COMMISSION_SERVICES {
        bigint id PK
        bigint artist_id FK
        varchar title
        decimal price_from
    }
    ARTIST_COMMISSION_REQUESTS {
        bigint id PK
        bigint artist_id FK
        bigint service_id FK "nullable"
        bigint customer_user_id FK
        enum status
    }
    ARTIST_FOLLOWS {
        bigint artist_id FK
        bigint user_id FK
    }

    USERS ||--|| ARTIST_PROFILES : "is (1:1)"
    ARTIST_PROFILES ||--o{ ARTIST_PORTFOLIO_ITEMS : displays
    ARTIST_PROFILES ||--o{ ARTIST_COMMISSION_SERVICES : offers
    ARTIST_PROFILES ||--o{ ARTIST_COMMISSION_REQUESTS : receives
    ARTIST_COMMISSION_SERVICES |o--o{ ARTIST_COMMISSION_REQUESTS : "requested via"
    USERS ||--o{ ARTIST_COMMISSION_REQUESTS : submits
    USERS ||--o{ ARTIST_FOLLOWS : follows
    ARTIST_PROFILES ||--o{ ARTIST_FOLLOWS : "followed by"
```

---

## 5. Multiverse Academy

```mermaid
erDiagram
    USERS {
        bigint id PK
    }
    ACADEMY_COURSES {
        bigint id PK
        bigint owner_user_id FK
        varchar title
        enum status
        enum level
    }
    ACADEMY_LESSONS {
        bigint id PK
        bigint course_id FK
        varchar title
        enum lesson_type
    }
    ACADEMY_ENROLLMENTS {
        bigint course_id FK
        bigint user_id FK
        enum status
        tinyint progress_percent
    }
    ACADEMY_INSTRUCTORS {
        bigint course_id FK
        bigint user_id FK
        enum role "lead|instructor|assistant|guest"
    }
    ACADEMY_LESSON_PROGRESS {
        bigint lesson_id FK
        bigint user_id FK
    }

    USERS ||--o{ ACADEMY_COURSES : creates
    ACADEMY_COURSES ||--o{ ACADEMY_LESSONS : contains
    USERS ||--o{ ACADEMY_ENROLLMENTS : enrolls
    ACADEMY_COURSES ||--o{ ACADEMY_ENROLLMENTS : has
    USERS ||--o{ ACADEMY_INSTRUCTORS : teaches
    ACADEMY_COURSES ||--o{ ACADEMY_INSTRUCTORS : has
    USERS ||--o{ ACADEMY_LESSON_PROGRESS : completes
    ACADEMY_LESSONS ||--o{ ACADEMY_LESSON_PROGRESS : "tracked by"
```

---

## 6. Collector Marketplace

Another independent, user-level storefront — same pattern as Artist Alley, different domain
(physical/digital collectibles instead of commissioned art).

```mermaid
erDiagram
    USERS {
        bigint id PK
    }
    COLLECTOR_PROFILES {
        bigint id PK
        bigint user_id FK "unique"
        varchar shop_name
        enum status
    }
    COLLECTOR_ITEMS {
        bigint id PK
        bigint collector_id FK
        varchar title
        enum listing_type "sale|trade|wanted|showcase"
        enum status
    }
    COLLECTOR_OFFERS {
        bigint id PK
        bigint item_id FK
        bigint buyer_user_id FK
        enum offer_type "cash|trade|message"
        enum status
    }
    COLLECTOR_FAVORITES {
        bigint user_id FK
        bigint item_id FK
    }

    USERS ||--|| COLLECTOR_PROFILES : "is (1:1)"
    COLLECTOR_PROFILES ||--o{ COLLECTOR_ITEMS : lists
    COLLECTOR_ITEMS ||--o{ COLLECTOR_OFFERS : receives
    USERS ||--o{ COLLECTOR_OFFERS : makes
    USERS ||--o{ COLLECTOR_FAVORITES : favorites
    COLLECTOR_ITEMS ||--o{ COLLECTOR_FAVORITES : "favorited as"
```

---

## 7. Admin, Imports & Platform Operations

Bulk CSV/TSV import of companies and brands (see `IMPORTS.md`), user invitations, and the
Developer Center's disposable demo-data generator all live here.

```mermaid
erDiagram
    USERS {
        bigint id PK
    }
    BRANDS {
        bigint id PK
        bigint import_batch_id FK "nullable"
    }
    INVITATIONS {
        bigint id PK
        varchar email
        enum invitation_type "member|admin"
        bigint invited_by FK
        bigint accepted_by FK "nullable"
    }
    IMPORT_BATCHES {
        bigint id PK
        enum entity_type "company|brand"
        bigint imported_by FK
        enum status
    }
    IMPORT_ITEMS {
        bigint id PK
        bigint batch_id FK
        enum action "imported|updated|skipped|error"
    }
    DEVELOPER_DEMO_BATCHES {
        bigint id PK
        bigint created_by FK
        enum scenario
        enum status
    }
    DEVELOPER_DEMO_RECORDS {
        bigint id PK
        bigint batch_id FK
        varchar table_name
    }

    USERS ||--o{ INVITATIONS : sends
    USERS |o--o{ INVITATIONS : accepts
    USERS ||--o{ IMPORT_BATCHES : runs
    IMPORT_BATCHES ||--o{ IMPORT_ITEMS : logs
    IMPORT_BATCHES |o--o{ BRANDS : "created/touched"
    USERS ||--o{ DEVELOPER_DEMO_BATCHES : generates
    DEVELOPER_DEMO_BATCHES ||--o{ DEVELOPER_DEMO_RECORDS : tracks
```

---

## Key rules worth remembering

- **`users` is the only identity table.** Artist, collector, booth-owner, company-member, and
  instructor are all roles a user takes on, not separate people records.
- **Brands always have exactly one parent company** (`brands.company_id NOT NULL`). Companies do
  not require a brand.
- **Booths are independent of the company/brand chain.** `company_id` and `brand_id` on `booths`
  are both nullable and unrelated to each other — a booth can stand alone, belong to a company,
  belong to a brand, or (in practice) both.
- **Universes are a shared taxonomy**, not owned by companies/brands. Booths, users, and events all
  tag into them, but universes don't belong to anyone.
- **Artist Alley and Collector Marketplace are parallel, solo-creator storefronts** off a single
  user — structurally siblings of booths, not children of them.
- **Events are the only entity with a polymorphic relationship** (`event_relationships`), letting
  one event reference any mix of company/brand/booth/universe without four nullable FK columns.
- Every submission-based entity (companies, brands, booths, events, courses, artist profiles)
  follows the same **draft → pending → approved/rejected** admin-review shape, usually with a
  paired `*_approval_history` audit table.

## Source of truth

| Module | Schema file |
|---|---|
| Users, identity, profiles, universes (base), invitations | `database/schema.sql`, `database/identity.sql`, `database/invitations.sql` |
| Companies | `database/companies.sql` |
| Brands + Import Center | `database/brands-imports.sql` |
| Universe hierarchy extras (moderators, activity) | `database/universe-engine.sql` |
| Universe Billboard (posts) | `database/universe-billboards.sql` |
| Universe live chat | `database/universe-community-v4.4.sql` |
| Booths & Marketplace foundation | `database/booths-marketplace.sql` |
| Booth management (team, gallery, downloads, views) | `database/booth-management-v5.1.sql` |
| Developer Center | `database/developer-center-v5.2.sql` |
| Events | `database/events-v6.sql` |
| Artist Alley | `database/artist-alley-v7.sql` |
| Multiverse Academy | `database/multiverse-academy-v8.sql` |
| Collector Marketplace | `database/collector-marketplace-v9.sql` |

If a table here doesn't match the live schema, the `.sql` file wins — this document is a snapshot,
regenerate the affected diagram by hand when a module's schema changes.
