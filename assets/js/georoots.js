const focusSection = document.querySelector('.focus-area');
const imagesToAnimate = document.querySelectorAll('.focus-animations img');

const focusAreaObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            imagesToAnimate.forEach(image => {
                image.classList.add('animate-in');
            });
            observer.unobserve(focusSection);
        }
    });
}, { threshold: 0.4 });

focusAreaObserver.observe(focusSection);


const faqQuestions = document.querySelectorAll('.faq-question');

faqQuestions.forEach(question => {
    question.addEventListener('click', () => {
        const currentItem = question.closest('.faq-item');
        const alreadyActiveItem = document.querySelector('.faq-accordion .faq-item.active');

        if (alreadyActiveItem && alreadyActiveItem !== currentItem) {
            alreadyActiveItem.classList.remove('active');
        }

        currentItem.classList.toggle('active');
    })
})