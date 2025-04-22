function ask_if_continue() {
  const userConfirmed = confirm("Czy na pewno chcesz utworzyć fakturę?");
  
  const form = document.querySelector("form");

  // Usuwamy wcześniej dodane pole (jeśli funkcja uruchamiana więcej niż raz)
  const oldInput = form.querySelector("input[name='confirmation']");
  if (oldInput) oldInput.remove();

  const confirmationInput = document.createElement("input");
  confirmationInput.type = "hidden";
  confirmationInput.name = "confirmation";
  confirmationInput.value = userConfirmed ? "yes" : "no";
  form.appendChild(confirmationInput);

  // ZAWSZE wysyłamy formularz
  form.submit();
}
