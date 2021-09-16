import React from "react";

export default class BaseScreen extends React.PureComponent {
  componentDidMount() {
    window.scrollTo(0, 0);
  }
}
