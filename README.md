## Auth

Auth deals with authenticating users via signup and login. It also sends
verification emails, has forgot and re-verify flows. Auth is built on top of
system, so it inherits all of its admin functionality as well.

## Install

```
composer install cradlephp/cradle-auth
```

### Routes

 - `GET /auth/signup` - Render the sign up page
 - `GET /auth/login` - Render the login page
 - `GET /auth/account` - Render the account form
 - `GET /auth/forgot` - Render the forgot password form
 - `GET /auth/recover/:auth_id/:hash` - Recover account flow
 - `GET /auth/verify` - Verification flow
 - `POST /auth/account` - Processes account form
 - `POST /auth/login` - Processes login form
 - `POST /auth/forgot` - Processes forgot password flow
 - `POST /auth/recover` - Processes the recover account flow
 - `POST /auth/signup` - Processes the signup form
 - `POST /auth/verify` - Processes the verification flow
 - `GET /auth/activate/:auth_id/:hash` - Recover password flow


### Events

 - `auth-create`
 - `auth-detail`
 - `auth-forgot`
 - `auth-forgot-mail`
 - `auth-remove`
 - `auth-restore`
 - `auth-search`
 - `auth-update`
 - `auth-login`
 - `auth-recover`
 - `auth-verify`
 - `auth-verify-mail`
