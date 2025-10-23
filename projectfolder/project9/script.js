const checkboxes = document.querySelectorAll("input[type=checkbox]");
const totalSpan = document.getElementById("total");
const totalInput = document.getElementById("total_input");

function calculateTotal() {
  let total = 0;

  // Base price: Executive room ₦45,000 per night
  const checkin = document.querySelector("input[name=checkin]").value;
  const checkout = document.querySelector("input[name=checkout]").value;

  if (checkin && checkout) {
    const d1 = new Date(checkin);
    const d2 = new Date(checkout);
    const nights = (d2 - d1) / (1000 * 60 * 60 * 24);
    if (nights > 0) {
      total += nights * 45000;
    }
  }

  checkboxes.forEach(cb => {
    if (cb.checked) {
      const price = cb.value.match(/— (\d+)/);
      if (price) {
        total += parseInt(price[1]);
      }
    }
  });

  totalSpan.textContent = total.toLocaleString();
  totalInput.value = total;
}

checkboxes.forEach(cb => cb.addEventListener("change", calculateTotal));
document.querySelectorAll("input[type=date]").forEach(d => d.addEventListener("change", calculateTotal));
