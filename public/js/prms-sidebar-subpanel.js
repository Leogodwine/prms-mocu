/**
 * Project & research — secondary sidebar (dynamic offset from parent)
 */
(function () {
  "use strict";

  var panel = document.getElementById("prmsWorkspaceSubpanel");
  var backdrop = document.getElementById("prmsWorkspaceSubpanelBackdrop");
  var toggle = document.getElementById("prmsWorkspaceSubnavToggle");
  var closeBtn = document.getElementById("prmsWorkspaceSubpanelClose");
  var triggerItem = toggle ? toggle.closest(".prms-workspace-trigger") : null;
  var wrapper = document.querySelector(".wrapper");
  var parentSidebar = document.querySelector(".wrapper > .sidebar:not(.prms-sidebar-sub)");
  var mobileMq = window.matchMedia("(max-width: 991.98px)");
  var parentMinimizedBeforePanel = null;
  var layoutObserver = null;

  if (!panel || !toggle) {
    return;
  }

  function isOpen() {
    return panel.classList.contains("is-open");
  }

  function shouldUseBackdrop() {
    return mobileMq.matches;
  }

  function syncSubpanelLayout() {
    if (!parentSidebar || mobileMq.matches) {
      document.documentElement.style.removeProperty("--prms-subpanel-offset");
      return;
    }

    var offset = Math.round(parentSidebar.getBoundingClientRect().width);
    document.documentElement.style.setProperty("--prms-subpanel-offset", offset + "px");
    panel.style.left = offset + "px";

    if (window.jQuery && isOpen()) {
      window.jQuery(window).trigger("resize");
    }
  }

  function bindLayoutSync() {
    syncSubpanelLayout();

    if (parentSidebar && typeof ResizeObserver !== "undefined") {
      layoutObserver = new ResizeObserver(syncSubpanelLayout);
      layoutObserver.observe(parentSidebar);
    }

    window.addEventListener("resize", syncSubpanelLayout);

    if (wrapper) {
      var classObserver = new MutationObserver(syncSubpanelLayout);
      classObserver.observe(wrapper, { attributes: true, attributeFilter: ["class"] });
    }

    if (parentSidebar) {
      parentSidebar.addEventListener("transitionend", syncSubpanelLayout);
      parentSidebar.addEventListener("mouseenter", syncSubpanelLayout);
      parentSidebar.addEventListener("mouseleave", syncSubpanelLayout);
    }

    document.querySelectorAll(".toggle-sidebar, .sidenav-toggler").forEach(function (btn) {
      btn.addEventListener("click", function () {
        window.setTimeout(syncSubpanelLayout, 50);
        window.setTimeout(syncSubpanelLayout, 320);
      });
    });
  }

  function syncMinimizeButtons(minimized) {
    document.querySelectorAll(".toggle-sidebar").forEach(function (btn) {
      if (minimized) {
        btn.classList.add("toggled");
        btn.innerHTML = '<i class="gg-more-vertical-alt"></i>';
      } else {
        btn.classList.remove("toggled");
        btn.innerHTML = '<i class="gg-menu-right"></i>';
      }
    });
  }

  function minimizeParentSidebar() {
    if (!wrapper) {
      return;
    }

    if (parentMinimizedBeforePanel === null) {
      parentMinimizedBeforePanel = wrapper.classList.contains("sidebar_minimize");
    }

    if (!wrapper.classList.contains("sidebar_minimize")) {
      wrapper.classList.add("sidebar_minimize");
      syncMinimizeButtons(true);
    }

    window.setTimeout(syncSubpanelLayout, 50);
    window.setTimeout(syncSubpanelLayout, 320);
  }

  function restoreParentSidebar() {
    if (!wrapper || parentMinimizedBeforePanel === null) {
      parentMinimizedBeforePanel = null;
      return;
    }

    if (parentMinimizedBeforePanel === false) {
      wrapper.classList.remove("sidebar_minimize");
      wrapper.classList.remove("sidebar_minimize_hover");
      syncMinimizeButtons(false);
    }

    parentMinimizedBeforePanel = null;
    window.setTimeout(syncSubpanelLayout, 50);
    window.setTimeout(syncSubpanelLayout, 320);
  }

  function syncBackdrop(open) {
    if (!backdrop) {
      return;
    }
    var showBackdrop = open && shouldUseBackdrop();
    backdrop.classList.toggle("is-visible", showBackdrop);
    backdrop.setAttribute("aria-hidden", showBackdrop ? "false" : "true");
  }

  function setOpen(open) {
    panel.classList.toggle("is-open", open);
    panel.setAttribute("aria-hidden", open ? "false" : "true");

    syncBackdrop(open);
    document.body.classList.toggle("prms-workspace-subnav-open", open);
    toggle.setAttribute("aria-expanded", open ? "true" : "false");

    if (triggerItem) {
      triggerItem.classList.toggle("is-subpanel-open", open);
    }

    if (open) {
      minimizeParentSidebar();
      syncSubpanelLayout();
    } else {
      restoreParentSidebar();
      syncSubpanelLayout();
    }
  }

  toggle.addEventListener("click", function (event) {
    event.preventDefault();
    setOpen(!isOpen());
  });

  if (closeBtn) {
    closeBtn.addEventListener("click", function () {
      setOpen(false);
    });
  }

  if (backdrop) {
    backdrop.addEventListener("click", function () {
      setOpen(false);
    });
  }

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && isOpen()) {
      setOpen(false);
    }
  });

  mobileMq.addEventListener("change", function () {
    syncSubpanelLayout();
    if (isOpen()) {
      syncBackdrop(true);
    }
  });

  bindLayoutSync();

  if (document.body.classList.contains("prms-workspace-subnav-open") || panel.classList.contains("is-open")) {
    setOpen(true);
  }
})();
