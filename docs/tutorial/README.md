# Tutorial: Build a Task Tracker

A step-by-step walkthrough that builds one small application — a task tracker with login-protected CRUD — touching most of the framework along the way. Each part builds on the last; follow them in order.

Unlike [Your First Application](../getting-started/first-application.md) (a quick single-page taste of routing + a model), this tutorial goes further: migrations, validation, authentication, middleware, server-rendered views, and tests, all wired into one working app.

1. [Setup](01-setup.md) — install, configure, and confirm the app boots
2. [Routes & Controllers](02-routes-and-controllers.md) — a `TaskController` behind real routes
3. [Database & Models](03-database-and-models.md) — a migration and a `Task` Active Record model
4. [Validation](04-validation.md) — reject bad input before it reaches the model
5. [Views](05-views.md) — server-rendered HTML with layouts and template inheritance
6. [Authentication & Middleware](06-authentication-and-middleware.md) — scope tasks to the logged-in user
7. [Testing](07-testing.md) — a Feature test covering the full request lifecycle
8. [Next Steps](08-next-steps.md) — where to go from here

By the end you'll have a working `/tasks` CRUD flow, gated behind login, with a passing test — and a mental map of how routing, models, validation, views, middleware, and tests fit together in this framework.
