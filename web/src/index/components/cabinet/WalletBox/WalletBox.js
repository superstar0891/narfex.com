import "./WalletBox.less";

import React from "react";
import SVG from "react-inlinesvg";

import * as utils from "../../../../utils";
import * as actions from "../../../../actions";
import * as UI from "../../../../ui/";

class WalletBox extends React.Component {
  constructor(props) {
    super(props);

    this.isGenerating = props.status === "pending";
    this.currencyInfo = actions.getCurrencyInfo(props.currency);
  }

  render() {
    let selected = false;

    this.className = utils.classNames({
      WalletBox: true,
      WalletBox__inactive: this.isGenerating,
      WalletBox__selected: selected,
      noAction: this.props.action === false
    });

    return (
      <div
        className={this.className}
        onClick={this.isGenerating ? () => {} : this.__onClick}
      >
        <UI.CircleIcon currency={this.currencyInfo} />

        <UI.ContentBox
          className="WalletBox__content"
          style={selected ? { background: this.currencyInfo.background } : {}}
        >
          {selected ? (
            <SVG
              className="WalletBox__close"
              src={require("./asset/close.svg")}
            />
          ) : (
            ""
          )}
          <h3>{utils.ucfirst(this.currencyInfo.name)}</h3>
          <p>{this.__getAmount()}</p>
        </UI.ContentBox>

        {this.isGenerating ? (
          <SVG
            className="WalletBox__loader"
            src={require("../../../../asset/cabinet/loading.svg")}
          />
        ) : null}
      </div>
    );
  }

  __getAmount = () => {
    if (this.isGenerating) {
      return utils.getLang("cabinet_walletBox_generating");
    } else if (this.props.amount > 0 || this.props.skipEmptyLabel) {
      return (
        <UI.NumberFormat
          number={this.props.amount}
          currency={this.props.currency}
        />
      );
    } else {
      return utils.getLang("cabinet_walletScreen_receive");
    }
  };

  __onClick = () => {
    if (this.props.action === false) {
      return false;
    }
    if (this.props.currency === "fndr") {
      return actions.openModal("nrfx_presale");
    }

    if (this.props.isFiat) {
      return this.props.onClick && this.props.onClick();
    }

    if (this.props.amount > 0 || this.props.skipEmptyLabel) {
      return this.props.onClick && this.props.onClick();
    } else {
      return actions.openModal("receive", {
        preset: utils.ucfirst(this.currencyInfo.name)
      });
    }
  };
}

WalletBox.defaultProps = {
  onClick: () => {}
};

export default WalletBox;
