import "./SendCoinsModal.less";

import React from "react";
import { connect } from "react-redux";
import big from "big.js";

import * as UI from "../../../../ui";

import * as actions from "../../../../actions";
import * as toast from "../../../../actions/toasts";
import * as walletsActions from "../../../../actions/cabinet/wallets";
import LoadingStatus from "../../cabinet/LoadingStatus/LoadingStatus";
import * as utils from "../../../../utils";
import Lang from "../../../../components/Lang/Lang";
import { Message } from "../../../../ui";
import { getCurrencyInfo } from "../../../../actions";

class SendCoinsModal extends React.Component {
  state = {
    status: null,
    addressError: false
  };

  componentDidMount() {
    this.__load();
  }

  render() {
    return (
      <UI.Modal
        className="SendCoinsModal__wrapper"
        isOpen={true}
        onClose={this.props.onClose}
      >
        <UI.ModalHeader>
          {utils.getLang("cabinet_sendCoinsModal_name")}
        </UI.ModalHeader>
        {this.__renderContent()}
      </UI.Modal>
    );
  }

  get currentWallet() {
    return this.props.wallets.find(
      w => w.id === this.props.walletId || w.currency === this.props.currency
    );
  }

  get currentFee() {
    return this.props.limits[this.currentWallet.currency].fee;
  }

  get currentMin() {
    return this.props.limits[this.currentWallet.currency].min;
  }

  __handleChange(property) {
    return value => {
      this.props.sendCoinModalSetValue(property, value);
      if (property === "address") {
        this.setState({ addressError: false });
      }
      if (property === "amount") {
        this.props.sendCoinModalSetValue(
          "amountUsd",
          value * this.currentWallet.to_usd
        );
      }
      if (property === "walletId") {
        ["amount", "amountUsd"].forEach(property =>
          this.props.sendCoinModalSetValue(property, "")
        );
      }
      if (property === "amountUsd") {
        this.props.sendCoinModalSetValue(
          "amount",
          value / this.currentWallet.to_usd
        );
      }
    };
  }

  get maxAmount() {
    const currentWallet = this.currentWallet;
    if (this.props.type === "address") {
      return big(currentWallet.amount)
        .minus(this.currentFee)
        .toPrecision();
    } else {
      return currentWallet.amount;
    }
  }

  __maxDidPress = () => {
    const currentWallet = this.currentWallet;
    const amount = this.maxAmount;
    const amountUsd = amount * currentWallet.to_usd;
    this.props.sendCoinModalSetValue(
      "amount",
      utils.formatDouble(Math.max(amount, 0))
    );
    this.props.sendCoinModalSetValue(
      "amountUsd",
      utils.formatDouble(Math.max(amountUsd, 0), 2)
    );
  };

  __handleSubmit = () => {
    if (this.props.amount > this.maxAmount) {
      toast.error(
        <>
          {utils.getLang("cabinet_sendCoinsModal_maximumAmountText")} :{" "}
          {utils.formatDouble(this.maxAmount)}{" "}
          {this.currentWallet.currency.toUpperCase()}
        </>
      );
      return false;
    }
    if (this.props.amount >= this.currentMin) {
      if (this.props.type === "login") {
        this.setState({ status: "loading" });
        walletsActions
          .checkLogin(this.props.login)
          .then(response => {
            this.setState({ addressError: false });
            actions.openModal("send_confirm");
          })
          .catch(response => {
            this.setState({ addressError: true });
          })
          .finally(() => {
            this.setState({ status: null });
          });
      } else {
        actions.openModal("send_confirm");
      }
    } else {
      toast.error(
        utils.getLang("cabinet_sendCoinsModal_minimumAmountText") +
          ": " +
          utils.formatDouble(this.currentMin) +
          " " +
          this.currentWallet.currency.toUpperCase()
      );
    }
  };

  __renderContent() {
    const types = {
      address: {
        label: utils.getLang("cabinet_sendCoinsModal_viaBlockchain"),
        inputPlaceholder: utils.getLang(
          "cabinet_sendCoinsModal_viaBlockchainInputPlaceholder"
        )
      },
      login: {
        label: utils.getLang("cabinet_sendCoinsModal_insidePlatform"),
        inputPlaceholder: utils.getLang(
          "cabinet_sendCoinsModal_insidePlatformInputPlaceholder"
        )
      }
    };

    if (this.props.loadingStatus) {
      return <LoadingStatus inline status="loading" />;
    }

    // const { wallets, walletId } = this.props;

    const currency = this.currentWallet.currency;

    console.log(currency);

    if (this.props.loadingStatus || !this.props.limits) {
      return <LoadingStatus inline status={this.props.loadingStatus} />;
    } else {
      return (
        <div className="SendCoinsModal">
          <div className="SendCoinsModal__row">
            <UI.SwitchTabs
              selected={this.props.type}
              onChange={this.__handleChange("type")}
              tabs={Object.keys(types).map(type => ({
                value: type,
                label: types[type].label
              }))}
            />
          </div>
          <Message>
            {this.props.type === "address" ? (
              <Lang name="cabinet_sendModal_InfoText_address" />
            ) : (
              <Lang name="cabinet_sendModal_InfoText_login" />
            )}
          </Message>
          <div className="SendCoinsModal__row">
            <UI.Input
              value={this.props[this.props.type]}
              placeholder={types[this.props.type].inputPlaceholder}
              onTextChange={this.__handleChange(this.props.type)}
              error={this.state.addressError}
            />
          </div>
          <div className="SendCoinsModal__row SendCoinsModal__amount">
            <UI.Input
              placeholder="0"
              decimalScale={getCurrencyInfo(currency)?.maximum_fraction_digits}
              indicator={this.currentWallet.currency.toUpperCase()}
              onTextChange={this.__handleChange("amount")}
              type="number"
              error={
                this.props.amount &&
                (this.props.amount < this.currentMin ||
                  this.props.amount > this.maxAmount)
              }
              value={this.props.amount}
              description={
                this.props.type === "address" ? (
                  <span>
                    <Lang name="global_fee" />
                    {": "}
                    <UI.NumberFormat
                      number={utils.formatDouble(this.currentFee)}
                      currency={currency}
                    />
                  </span>
                ) : (
                  <span className="cabinet_sendCoinsModal__noFee">
                    <Lang name="global_noFee" />
                  </span>
                )
              }
            />
            <UI.Tooltip
              title={utils.getLang("cabinet_sendCoinsModal_tooltipText")}
            >
              <UI.Input
                placeholder="0"
                decimalScale={2}
                indicator="USD"
                type="number"
                onTextChange={this.__handleChange("amountUsd")}
                value={this.props.amountUsd}
              />
            </UI.Tooltip>
            <UI.Button
              smallPadding
              type="secondary"
              currency={currency}
              onClick={this.__maxDidPress}
            >
              {utils.getLang("cabinet_sendCoinsModal_max")}
            </UI.Button>
          </div>
          <div className="SendCoinsModal__submit_wrap">
            <UI.Button
              state={this.state.status}
              currency={currency}
              onClick={this.__handleSubmit}
              disabled={
                !(
                  this.props.amount &&
                  ((this.props.type === "address" && this.props.address) ||
                    (this.props.type === "login" && this.props.login))
                )
              }
            >
              {utils.getLang("global_send")}
            </UI.Button>
          </div>
        </div>
      );
    }
  }

  __load = () => {
    walletsActions.getWallets();
    this.props.getLimits();
  };
}

export default connect(
  state => ({
    loadingStatus:
      state.wallets.loadingStatus.limits || state.wallets.loadingStatus.default,
    wallets: state.wallets.wallets,
    limits: state.wallets.limits,
    ...state.wallets.sendCoinModal
  }),
  {
    sendCoinModalSetValue: walletsActions.sendCoinModalSetValue,
    getLimits: walletsActions.getLimits
  }
)(SendCoinsModal);
