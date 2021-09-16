import { Component } from "react";

Object.defineProperty(Component.prototype, "adaptive", {
  get: function() {
    if (this.props.hasOwnProperty("adaptive")) {
      return this.props.adaptive;
    } else {
      return document.body.classList.contains("adaptive");
    }
  }
});
