const signUpBtn = document.getElementById("signUp");
const signInBtn = document.getElementById("signIn");
const container = document.querySelector(".container");

signUpBtn?.addEventListener("click", () => {
  container.classList.add("right-panel-active");
});
signInBtn?.addEventListener("click", () => {
  container.classList.remove("right-panel-active");
});

document.addEventListener('DOMContentLoaded', () => {
    // 메뉴 스크롤 기능
    const links = document.querySelectorAll('.menu a');
    links.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = link.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);
            if (targetSection) {
                window.scrollTo({
                    top: targetSection.offsetTop - 50,
                    behavior: 'smooth'
                });
            }
        });
    });

    // 배경 이미지 슬라이드
    const images = [
        '../img/su_1.jpg',
        '../img/su_2.jpg',
        '../img/su_3.jpg'
    ];
    let currentIndex = 0;
    const body = document.body;

    setInterval(() => {
        currentIndex = (currentIndex + 1) % images.length;
        body.style.backgroundImage = `url('${images[currentIndex]}')`;
        body.style.transition = 'background-image 1s ease-in-out';
    }, 5000); // 5초마다 변경
});