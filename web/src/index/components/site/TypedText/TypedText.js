/* eslint-disable */

import "./TypedText.less";

import React from "react";
import { connect } from "react-redux";
import { getLang } from "../../../../utils";

let currentProductIndex = 0;

class TypedText extends React.PureComponent {
  animationTimer = null;

  state = {
    currentKey: this.props.products[0],
    currentString: ""
  };

  componentDidMount() {
    this.typeMessage();
  }

  typeMessage() {
    const { products } = this.props;
    this.setState({
      currentKey: products[currentProductIndex]
    });

    const currentProduct = getLang(products[currentProductIndex], true);
    const currentProductArr = currentProduct ? currentProduct.split("") : [];
    let curString = "";
    let currentLetter = 0;
    let int1 = setInterval(() => {
      if (!currentProductArr[currentLetter]) {
        if (currentProductIndex < products.length - 1) {
          currentProductIndex++;
        } else {
          currentProductIndex = 0;
        }
        this.animationTimer = setTimeout(() => {
          this.deleteMessage(curString);
        }, 500);
        clearInterval(int1);
      } else {
        curString += currentProduct[currentLetter++];
        this.setState({ currentString: curString });
      }
    }, 100);
  }

  deleteMessage(str) {
    let int = setInterval(() => {
      if (str.length === 0) {
        this.animationTimer = setTimeout(() => {
          this.typeMessage();
        }, 500);
        clearInterval(int);
      } else {
        str = str.split("");
        str.pop();
        str = str.join("");
        this.setState({ currentString: str });
      }
    }, 50);
  }

  componentWillUnmount() {
    clearTimeout(this.animationTimer);
  }

  render() {
    const { currentString, currentKey } = this.state;

    return (
      <div className="TypedText">{getLang(currentKey, currentString)}</div>
    );
  }
}

export default connect(state => ({
  translatorMode: state.default.profile.user && state.settings.translator
}))(TypedText);
