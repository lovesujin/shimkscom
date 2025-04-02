// debounce 함수: 특정 이벤트가 연속적으로 발생할 때, 마지막 이벤트가 발생한 후 일정 시간 동안 추가 이벤트가 발생하지 않을 경우에만 실행
// 주로 입력 필드에서 사용하여 불필요한 API 호출을 방지하는 데 유용
function debounce(func, delay) {
    let timer; // 타이머 ID를 저장할 변수
    return function (...args) {
        const context = this; // 호출된 함수의 컨텍스트를 유지
        clearTimeout(timer); // 이전 타이머를 제거하여 중복 호출 방지
        timer = setTimeout(() => func.apply(context, args), delay); // delay 후에 함수 실행
    };
}

// throttle 함수: 특정 이벤트가 연속적으로 발생할 때, 일정 시간 간격으로만 실행
// 주로 스크롤 이벤트나 리사이즈 이벤트에서 성능 최적화를 위해 사용
function throttle(func, limit) {
    let lastFunc; // 마지막으로 실행된 함수의 타이머 ID
    let lastRan; // 마지막으로 실행된 시간
    return function (...args) {
        const context = this; // 호출된 함수의 컨텍스트를 유지
        const now = Date.now(); // 현재 시간
        if (!lastRan) {
            func.apply(context, args); // 처음 호출 시 즉시 실행
            lastRan = now;
        } else {
            clearTimeout(lastFunc); // 이전 타이머 제거
            lastFunc = setTimeout(() => {
                if (now - lastRan >= limit) { // limit 시간이 지난 경우에만 실행
                    func.apply(context, args);
                    lastRan = now;
                }
            }, limit - (now - lastRan));
        }
    };
}

// 예제: debounce와 throttle 사용
const logDebounce = debounce(() => console.log('Debounced!'), 300);
const logThrottle = throttle(() => console.log('Throttled!'), 300);

// 이벤트 리스너에 적용
window.addEventListener('resize', logDebounce); // 리사이즈 이벤트에 debounce 적용
window.addEventListener('scroll', logThrottle); // 스크롤 이벤트에 throttle 적용