const numberInput = document.getElementById("numbers-input");
const numberText = document.getElementById("numbers-text");
numberInput.maxLength = 19;

const nameInput = document.getElementById("name-input");
const nameText = document.getElementById("name-text");


const expiryInput = document.getElementById("expiry-input");
const expiryText = document.getElementById("expiry-text");

const cardDisplay = document.getElementById("card-display");
const cvcText = document.getElementById("cvc-text");

const cvcInput = document.getElementById("cvc-input");
cvcInput.maxLength = 3;

const cardType = document.getElementById("type-img");

const nameDiv = document.getElementById("name-div");

const messageElement = document.getElementById("wrong-number-message");

const cardNumDisplay = document.querySelector(".card-numbers-display");

numberInput.addEventListener("input", () => {
    let numValue = numberInput.value.replace(/\D/g, "").slice(0, 19);
    numValue = numValue.replace(/(.{4})/g, "$1 ").trim();
    numberInput.value = numValue;

    const allSpans = cardNumDisplay.querySelectorAll("span");
    for (let i = 0; i < numValue.length; i++) {
        const char = numValue[i];
        const exists = allSpans[i];

    if (exists) {
        if (exists.textContent !== char) {
            exists.textContent = char;
            exists.classList.add("roll");
        if (char === " ") {
            exists.classList.add("space")
        } else {
            exists.classList.remove("space")};
            exists.addEventListener("animationend", () => {
            exists.classList.remove("roll");
        });
      }
    } else {
      const newSpan = document.createElement("span");
      newSpan.classList.add("numbers-text");
      newSpan.textContent = char;
      newSpan.classList.add("roll");

      if (char === " ") {
        newSpan.classList.add("space");
      }

    cardNumDisplay.appendChild(newSpan);
    }
  }

    while (cardNumDisplay.children.length > numValue.length) {
        cardNumDisplay.removeChild(cardNumDisplay.lastChild);
    }


    let firstDigit = numValue.charAt(0);
        switch (firstDigit) {
            case '3':
                    cardType.src = "../img/american.png";
                break;
            case '4':
                    cardType.src = "../img/visapng.png";
                break;
            case '5':
                    cardType.src = "../img/master.png";
                break;
            case '6':
                    cardType.src = "../img/discover.png";
                break;
            default:
                    cardType.src = "../img/unknown-type.png";
                break;
        }

        const validNumList = [3,4,5,6];

        if (numberInput.value.length === 0) {
            messageElement.style.display = "none";
        } else {
            firstDigit = parseInt(numberInput.value[0], 10);

        if (!validNumList.includes(firstDigit)) {
            message = "Invalid card number";
            messageElement.style.display = "block";
        } else {
            messageElement.style.display = "none";
        }
    }

    const value = numberInput.value.replace(/\D/g, "").slice(0, 16);

});


nameInput.addEventListener("input", () => {
    let nameToShow = nameInput.value.replace(/[^a-zA-Z\s]/g, "");
    nameText.textContent = nameToShow;

    console.log(nameText.offsetWidth);
    const width = nameText.offsetWidth;

    if (width > 220 && !nameText.innerHTML.includes("<br>")) {
        const half = Math.floor(nameText.textContent.length / 2);
        nameText.innerHTML = nameText.textContent.slice(0, half) + "<br>" + nameText.textContent.slice(half);
        nameText.style.fontSize = "13px";
    }
});

expiryInput.addEventListener("input", () => {
    const expiryToShow = expiryInput.value;
    expiryText.innerHTML = expiryToShow;

    const expiryTextArr = expiryToShow.split("-");
    console.log(expiryTextArr);
    expiryText.innerHTML = expiryTextArr[1] + "/" + expiryTextArr[0].slice(2,4);
});

cvcInput.addEventListener("focus", () => {
    cardDisplay.classList.add("flip");
})
cvcInput.addEventListener("blur", () => {
    cardDisplay.classList.remove("flip");
})

cvcInput.addEventListener("input", () => {
    let cvcVal = cvcInput.value.replace(/\D/g, "").slice(0,3);
    cvcInput.value = cvcVal;
    cvcText.textContent = cvcVal;
});
