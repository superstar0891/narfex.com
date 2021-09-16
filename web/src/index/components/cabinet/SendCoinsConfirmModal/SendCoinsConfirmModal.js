import "./SendCoinsConfirmModal.less";

import React from "react";
import { connect } from "react-redux";
import * as UI from "../../../../ui";
import SVG from "react-inlinesvg";

import * as actions from "../../../../actions";
import * as utils from "../../../../utils";
import * as walletsActions from "../../../../actions/cabinet/wallets";
import NumberFormat from "../../../../ui/components/NumberFormat/NumberFormat";

class SendCoinsConfirmModal extends React.Component {
  render() {
    if (!this.props.amount || this.props.loadingStatus.send === "success") {
      this.props.onClose();
      return false;
    }

    return (
      <UI.Modal
        className="SendCoinsConfirmModal__wrapper"
        isOpen={true}
        onClose={this.props.onClose}
        width={464}
      >
        <UI.ModalHeader>{this.__getTitle()}</UI.ModalHeader>
        {this.__renderContent()}
      </UI.Modal>
    );
  }

  get currentWallet() {
    return this.props.wallets.find(w => w.id === this.props.walletId);
  }

  __handleChange = value => {
    this.props.sendCoinModalSetValue("gaCode", value);
    if (!this.props.gaEnabled || value.length === 6) {
      this.__handleSubmit(value);
    }
  };

  __getTitle() {
    const currencyInfo = actions.getCurrencyInfo(this.currentWallet.currency);
    return (
      <span>
        {utils.getLang("cabinet_sendCoinsConfirmModal_name")}{" "}
        {utils.ucfirst(currencyInfo.name)}
      </span>
    );
  }

  get currentFee() {
    if (this.props.type === "login") return 0;
    return this.props.limits[this.currentWallet.currency].fee;
  }

  __handleSubmit = gaCode => {
    this.props.sendCoins({
      type: this.props.type,
      login: this.props.login,
      address: this.props.address,
      wallet_id: this.props.walletId,
      amount: this.props.amount,
      ga_code: gaCode || this.props.gaCode
    });
  };

  __renderContent() {
    const { address, login, amount, gaCode = "" } = this.props;
    const currencyInfo = actions.getCurrencyInfo(this.currentWallet.currency);

    return (
      <div className="SendCoinsConfirmModal">
        <UI.List
          items={[
            {
              label: utils.getLang("global_from"),
              value: (
                <span>
                  {utils.getLang("cabinet_walletTransactionModal_my")}{" "}
                  {utils.ucfirst(currencyInfo.name)}{" "}
                  {utils.getLang("global_wallet")}
                </span>
              )
            },
            { label: utils.getLang("global_to"), value: address || login },
            {
              label: utils.getLang("global_amount"),
              value: (
                <NumberFormat number={amount} currency={currencyInfo.abbr} />
              )
            },
            {
              label: utils.getLang("global_fee"),
              value: (
                <NumberFormat
                  number={this.currentFee}
                  currency={currencyInfo.abbr}
                />
              )
            }
          ]}
        />

        <UI.WalletCard
          title={utils.getLang("cabinet_sendCoinsConfirmModal_total")}
          balance={parseFloat(amount) + this.currentFee}
          currency={currencyInfo.abbr}
        />

        {this.props.gaEnabled && (
          <UI.Input
            autoFocus
            type="code"
            cell
            autoComplete="off"
            mouseWheel={false}
            maxLength={6}
            value={gaCode}
            onTextChange={this.__handleChange}
            placeholder={utils.getLang("site__authModalGAPlaceholder", true)}
            error={
              gaCode.length === 6 &&
              this.props.loadingStatus.sendCode === "ga_auth_code_incorrect"
            }
            indicator={
              <SVG src={require("../../../../asset/google_auth.svg")} />
            }
          />
        )}

        <div className="SendCoinsConfirmModal__submit_wrapper">
          <UI.Button
            state={this.props.loadingStatus.send}
            currency={currencyInfo}
            onClick={() => this.__handleSubmit()}
            disabled={this.props.gaEnabled && gaCode.length !== 6}
          >
            {utils.getLang("site__authModalSubmit")}
          </UI.Button>
        </div>
      </div>
    );
  }
}

export default connect(
  state => ({
    loadingStatus: state.wallets.loadingStatus,
    wallets: state.wallets.wallets,
    gaEnabled: state.default.profile.ga_enabled,
    limits: state.wallets.limits,
    ...state.wallets.sendCoinModal,
    gaCode: state.wallets.sendCoinModal.gaCode
  }),
  {
    sendCoins: walletsActions.sendCoins,
    sendCoinModalSetValue: walletsActions.sendCoinModalSetValue
  }
)(SendCoinsConfirmModal);
