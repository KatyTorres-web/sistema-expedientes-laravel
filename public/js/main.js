document.addEventListener("input", function (e) {
    if (e.target.tagName === "INPUT" || e.target.tagName === "TEXTAREA") {
        if (
            e.target.type !== "password" &&
            e.target.type !== "email" &&
            e.target.name !== "username"
        ) {
            e.target.value = e.target.value.toUpperCase();
        }
    }
});
