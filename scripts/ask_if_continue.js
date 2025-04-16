function ask_if_continue() {
  const userConfirmed = confirm("Czy na pewno chcesz utworzyć fakturę?");

  const form = document.querySelector("form");

  const confirmationInput = document.createElement("input");
  confirmationInput.type = "hidden";
  confirmationInput.name = "confirmation";
  confirmationInput.value = userConfirmed ? "yes" : "no";
  form.appendChild(confirmationInput);

  if (userConfirmed) {
    const actionInput = document.createElement("input");
    actionInput.type = "hidden";
    actionInput.name = "action";
    actionInput.value = "tworz_fakture";
    form.appendChild(actionInput);

    form.submit();
  } else {
    console.log("Użytkownik anulował tworzenie faktury");
  }
}
