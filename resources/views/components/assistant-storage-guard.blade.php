<script>
    if (/(^|\/)login(\/|$)/.test(window.location.pathname)) {
        Object.keys(window.localStorage)
            .filter((key) => key.startsWith('kairo_assistant_chat_'))
            .forEach((key) => window.localStorage.removeItem(key));
        window.sessionStorage.removeItem('kairo_assistant_open');
    }
</script>
