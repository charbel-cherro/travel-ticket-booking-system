(function () {
  const tabs = document.querySelectorAll("#tripTabs .tab");
  const returnField = document.getElementById("returnField");

  if (!tabs.length || !returnField) return;

  tabs.forEach((btn) => {
    btn.addEventListener("click", () => {
      tabs.forEach((t) => t.classList.remove("active"));
      btn.classList.add("active");

      const mode = btn.dataset.mode;
      // One Way hides return date (UI only)
      returnField.style.display = (mode === "oneway") ? "none" : "flex";
    });
  });
})();