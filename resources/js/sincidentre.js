// Animate counters for reports
function animateCounter(id, target) {
  const el = document.getElementById(id);
  let count = 0;
  const step = Math.ceil(target / 50);

  const interval = setInterval(() => {
    count += step;
    if (count >= target) {
      count = target;
      clearInterval(interval);
    }
    el.textContent = count;
  }, 30);
}

// HOME JS

document.addEventListener("DOMContentLoaded", () => {
  console.log("Sincidentre Home Page Loaded ✅");

  // Example: Smooth scroll for navbar links
  document.querySelectorAll("a[href^='#']").forEach(anchor => {
    anchor.addEventListener("click", function(e) {
      e.preventDefault();
      document.querySelector(this.getAttribute("href")).scrollIntoView({
        behavior: "smooth"
      });
    });
  });
});



// Animate report numbers on load
document.addEventListener("DOMContentLoaded", () => {
  animateCounter("total-reports", 12);
  animateCounter("pending-reports", 3);
  animateCounter("resolved-reports", 9);

  // Search filter
  const searchInput = document.getElementById("search-input");
  const table = document.getElementById("reports-table").getElementsByTagName("tbody")[0];

  searchInput.addEventListener("keyup", function() {
    const filter = searchInput.value.toLowerCase();
    const rows = table.getElementsByTagName("tr");
    for (let i = 0; i < rows.length; i++) {
      const text = rows[i].textContent.toLowerCase();
      rows[i].style.display = text.includes(filter) ? "" : "none";
    }
  });

  // Logout confirmation
  document.getElementById("logout-btn").addEventListener("click", (e) => {
    e.preventDefault();
    if (confirm("Are you sure you want to log out?")) {
      window.location.href = "#"; // keep backend intact
    }
  });
});
