import React from "react";
// internal
import BaseScreen from "./BaseScreen";
import * as UI from "../../ui";

export default class TestScreen extends BaseScreen {
  render() {
    return (
      <div>
        <h1>Message: {this.props.testMessage}</h1>
        <br />
        <UI.Button onClick={() => this.props.update()}>Click</UI.Button>
      </div>
    );
  }
}
