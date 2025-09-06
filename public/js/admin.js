document.addEventListener("DOMContentLoaded", () => {
    console.log("Admin dashboard loaded.");

    const navCards = document.querySelectorAll('.nav-card');

    // Tự động active thẻ đầu tiên không bị ẩn
    // const firstVisibleCard = Array.from(navCards).find(card => card.offsetParent !== null);
    // if (firstVisibleCard) {
    //     firstVisibleCard.classList.add('active');
    // }

    navCards.forEach(card => {
        card.addEventListener('click', function () {
            navCards.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            console.log(`Navigating to: ${this.textContent.trim()}`);
        });
    });
});