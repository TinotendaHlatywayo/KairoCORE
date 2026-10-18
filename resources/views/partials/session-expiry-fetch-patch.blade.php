{{-- Session-expiry hard-navigation patch.
     When the session dies while a Filament/Livewire SPA is open, the XHR gets
     401/419 JSON from the renderables in bootstrap/app.php instead of a page.
     This wrapper notices those statuses on same-origin fetches and hard-
     navigates to the panel's login page (carrying the session_expired notice)
     so the user sees a clear message instead of a UI that silently stops
     responding. --}}
<script>
    (function () {
        const originalFetch = window.fetch;

        function sessionExpiredLoginUrl(redirect) {
            if (redirect) {
                return redirect;
            }
            const segment = window.location.pathname.split('/')[1];
            const login = segment === 'platform'
                ? '/platform/login'
                : (segment === 'student' ? '/student/login' : '/workspace/login');
            return login + '?notice=session_expired';
        }

        window.fetch = function (...args) {
            return originalFetch.apply(this, args).then(function (response) {
                if (response.status === 401 || response.status === 419) {
                    const requestUrl = typeof args[0] === 'string'
                        ? args[0]
                        : (args[0] && args[0].url ? args[0].url : '');
                    const sameOrigin = !requestUrl
                        || requestUrl.startsWith('/')
                        || requestUrl.startsWith(window.location.origin);

                    if (sameOrigin) {
                        response.clone().json()
                            .then(function (data) {
                                window.location.assign(sessionExpiredLoginUrl(data && data.redirect ? data.redirect : null));
                            })
                            .catch(function () {
                                window.location.assign(sessionExpiredLoginUrl(null));
                            });
                    }
                }

                return response;
            });
        };
    })();
</script>
