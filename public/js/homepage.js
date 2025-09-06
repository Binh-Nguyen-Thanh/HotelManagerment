function toggleDropdown() {
    var dropdown = document.getElementById("userDropdown");
    dropdown.classList.toggle("show");
}

// Đóng dropdown nếu bấm ra ngoài
window.onclick = function (event) {
    if (!event.target.matches('.user-menu')) {
        var dropdowns = document.getElementsByClassName("dropdown-content");
        for (var i = 0; i < dropdowns.length; i++) {
            if (dropdowns[i].classList.contains('show')) {
                dropdowns[i].classList.remove('show');
            }
        }
    }
}

// lam moi hoac cuon xuong noi dung hien dan ra
document.addEventListener("DOMContentLoaded", function () {
    const fadeSections = document.querySelectorAll('.fade-in-section');

    const options = {
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target); // chỉ hiện một lần
            }
        });
    }, options);

    fadeSections.forEach(section => {
        observer.observe(section);
    });
});


// slick
$(document).ready(function () {
    $('.carousel-container').slick({
        slidesToShow: 2.9,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 1500,
        arrows: false,
        dots: true,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 2,
                }
            },
            {
                breakpoint: 600,
                settings: {
                    slidesToShow: 1,
                }
            }
        ]
    });
});

// câu hỏi
document.addEventListener('DOMContentLoaded', function () {
    const faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');

        question.addEventListener('click', () => {
            // Chỉ toggle câu hỏi được click, không ảnh hưởng đến câu hỏi khác
            item.classList.toggle('active');

            // Cuộn mượt đến câu hỏi nếu nó được mở
            if (item.classList.contains('active')) {
                setTimeout(() => {
                    item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 100);
            }
        });
    });
});

// nhan vien
document.addEventListener('DOMContentLoaded', function () {
    const teamMembers = document.querySelectorAll('.team-member');
    let touchTimer;

    // Preload images for smoother animation
    const preloadImages = () => {
        const images = document.querySelectorAll('.member-image img');
        images.forEach(img => {
            const imgSrc = img.getAttribute('src');
            if (imgSrc) {
                new Image().src = imgSrc;
            }
        });
    };

    // Handle hover and touch events
    const setupTeamMembers = () => {
        teamMembers.forEach(member => {
            // Mouse enter (desktop)
            member.addEventListener('mouseenter', function () {
                resetAllMembers();
                this.classList.add('active');
            });

            // Mouse leave (desktop)
            member.addEventListener('mouseleave', function () {
                this.classList.remove('active');
            });

            // Touch start (mobile)
            member.addEventListener('touchstart', function (e) {
                e.preventDefault();
                resetAllMembers();
                touchTimer = setTimeout(() => {
                    this.classList.add('active');
                }, 300); // 300ms delay for long press
            }, { passive: false });

            // Touch end (mobile)
            member.addEventListener('touchend', function (e) {
                e.preventDefault();
                clearTimeout(touchTimer);
            });

            // Touch cancel (mobile)
            member.addEventListener('touchcancel', function () {
                clearTimeout(touchTimer);
            });
        });
    };

    // Reset all team members to inactive state
    const resetAllMembers = () => {
        teamMembers.forEach(member => {
            member.classList.remove('active');
        });
    };

    // Initialize
    preloadImages();
    setupTeamMembers();

    // Close card when clicking outside
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.team-member')) {
            resetAllMembers();
        }
    });
});

// footer
document.addEventListener('DOMContentLoaded', function () {

    const footer = document.querySelector('.site-footer');
    const observerOptions = {
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                footer.style.opacity = '1';
                footer.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    footer.style.opacity = '0';
    footer.style.transform = 'translateY(20px)';
    footer.style.transition = 'opacity 0.5s ease, transform 0.5s ease';

    observer.observe(footer);
});

// model liên kết 
function openInfoModal(title, content) {
    document.getElementById('modal-title').textContent = title;
    document.getElementById('modal-body').innerHTML = content;
    document.getElementById('infoModal').style.display = 'flex';
}

function closeInfoModal() {
    document.getElementById('infoModal').style.display = 'none';
}

// Model đăng nhập hay chưa

function handleBookClick(event) {
    event.preventDefault();

    const targetUrl = event.currentTarget.getAttribute('data-url');

    if (isLoggedIn) {
        window.location.href = targetUrl; // Đi tới URL được chỉ định
    } else {
        // Mở modal yêu cầu đăng nhập
        document.getElementById('loginModal').style.display = 'flex';

        // Cập nhật nút "Đồng ý" trong modal để đi đúng đường dẫn đăng nhập + redirect về URL mong muốn
        const loginBtn = document.querySelector('#loginModal .btn-agree');
        loginBtn.setAttribute('href', `${loginUrl}?redirect_to=${encodeURIComponent(targetUrl)}`);
    }
}

function closeModal() {
    document.getElementById('loginModal').style.display = 'none';
}