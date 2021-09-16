import "./WithdrawalModal.less";

import React from "react";
import { connect } from "react-redux";

import * as UI from "../../../../ui";
import * as investmentsActions from "../../../../actions/cabinet/investments";
import * as utils from "../../../../utils";
import ModalState from "../ModalState/ModalState";
import * as actions from "../../../../actions";
import * as toasts from "../../../../actions/toasts";
import NumberFormat from "../../../../ui/components/NumberFormat/NumberFormat";

class WithdrawalModal extends React.Component {
  state = {
    loadingStatus: "loading",
    amount: "",
    gaCode: "",
    errorGaCode: false,
    success: false,
    available: 0,
    availableWithoutDrop: 0,
    currency: null,
    walletId: null
  };

  componentDidMount() {
    this.__load();
  }

  __load = () => {
    investmentsActions
      .getWithdraw(this.props.currency)
      .then(withdraw => {
        this.setState({
          available: withdraw.available,
          availableWithoutDrop: withdraw.available_without_drop,
          currency: this.props.currency,
          walletId: withdraw.wallet.id,
          loadingStatus: ""
        });
      })
      .catch(err => {
        this.setState({ loadingStatus: "failed" });
      });
  };

  render() {
    if (this.state.loadingStatus) {
      return (
        <ModalState status={this.state.loadingStatus} onRetry={this.__load} />
      );
    }

    const currencyInfo = actions.getCurrencyInfo(this.state.currency);

    return (
      <UI.Modal
        className="WithdrawalModal__wrapper"
        noSpacing
        isOpen={true}
        onClose={this.props.onClose}
      >
        <UI.ModalHeader>{utils.getLang("withdraw_Income")}</UI.ModalHeader>
        {!this.state.success ? (
          <div className="WithdrawalModal">
            <div className="WithdrawalModal__info_row">
              <div className="WithdrawalModal__info_row__title">
                {utils.getLang("cabinet_withdrawalModal_beAware")}
              </div>
              <div className="WithdrawalModal__info_row__caption">
                {utils.getLang("cabinet_withoutCaption")}{" "}
                <NumberFormat
                  number={this.state.availableWithoutDrop}
                  currency={this.state.currency}
                />{" "}
                {utils.getLang("cabinet_withoutCaption2")}
              </div>
            </div>
            <div className="WithdrawalModal__info_row">
              <div className="WithdrawalModal__info_row__title">
                {utils.getLang("cabinet_withdrawalModal_attention")}
              </div>
              <div className="WithdrawalModal__info_row__caption">
                {utils.getLang("cabinet_withdrawalModal_moreThanText")}
              </div>
            </div>
            <div className="WithdrawalModal__row WithdrawalModal__row_amount">
              <div className="WithdrawalModal__row_amount__input">
                <UI.Input
                  autoFocus
                  placeholder="0"
                  type="number"
                  indicator={this.state.currency.toUpperCase()}
                  indicatorWidth={32}
                  onTextChange={amount => {
                    this.setState({ amount });
                  }}
                  value={this.state.amount}
                  error={this.state.amount > this.state.available}
                />
                <p className="Form__helper__text">
                  {utils.getLang("global_available")}:{" "}
                  <NumberFormat
                    number={this.state.available}
                    currency={this.state.currency}
                  />
                </p>
              </div>
              <UI.Button
                currency={this.state.currency}
                type="secondary"
                smallPadding
                onClick={() => this.__maxDidPress(this.state.available)}
              >
                {utils.getLang("cabinet_withdrawalModal_max")}
              </UI.Button>
            </div>
            {this.state.amount > this.state.availableWithoutDrop && (
              <p
                className="WithdrawalModal__without_drop_info"
                style={{ color: currencyInfo.color }}
              >
                {utils.getLang("cabinet_withoutDropInfo")}{" "}
                {this.state.currency.toUpperCase()}{" "}
                {utils.getLang("cabinet_withoutDropInfo2")}
              </p>
            )}
            {this.props.gaEnabled && (
              <div className="WithdrawalModal__row">
                <UI.Input
                  type="code"
                  autoComplete="off"
                  indicator={<div className="OpenDepositModal__ga_icon" />}
                  value={this.state.gaCode}
                  onChange={this.__handleGAChange}
                  placeholder={utils.getLang(
                    "site__authModalGAPlaceholder",
                    true
                  )}
                  onKeyPress={this.__onKeyPressHandler}
                  error={this.state.errorGaCode}
                  disabled={this.state.amount.length < 1}
                />
              </div>
            )}
            <div className="WithdrawalModal__button_wrap">
              <UI.Button
                currency={this.state.currency}
                style={{ width: "208px" }}
                onClick={this.__handleSubmit}
                disabled={!this.__formIsValid()}
              >
                {utils.getLang("general_withdraw")}
              </UI.Button>
            </div>
            <UI.CircleIcon
              className="WithdrawalModal__icon"
              currency={currencyInfo}
            />
          </div>
        ) : (
          <div className="WithdrawalModal success">
            <div
              className="WithdrawalModal__success_icon"
              style={{
                backgroundImage: `url(${require("../../../../asset/120/success.svg")})`
              }}
            />
            <h4>{utils.getLang("cabinet_withdrawalModal_successTitle")}</h4>
            <p>{utils.getLang("cabinet_withdrawalModal_successText")}</p>
            <UI.Button
              currency={this.state.currency}
              style={{ width: "208px" }}
              onClick={this.props.onClose}
            >
              {utils.getLang("global_ok")}
            </UI.Button>
          </div>
        )}
      </UI.Modal>
    );
  }

  __onKeyPressHandler = e => {
    utils.InputNumberOnKeyPressHandler(e);
    if (e.key === "Enter" && this.state.gaCode.length < 6) {
      this.__handleSubmit();
    }
  };

  __formIsValid = () => {
    return (
      (!this.props.gaEnabled || this.state.gaCode.length === 6) &&
      this.state.amount > 0
    );
  };

  __maxDidPress = max => {
    this.setState({ amount: max });
  };

  __handleGAChange = e => {
    const val = e.target.value;

    if (val.length <= 6) {
      this.setState({ gaCode: val }, () => {
        if (val.length === 6) {
          this.__handleSubmit();
        }
      });
    }
  };

  __buildParams() {
    return {
      wallet_id: this.state.walletId,
      amount: this.state.amount,
      ga_code: this.state.gaCode
    };
  }

  __inputError(node, stateField) {
    node.setState(
      {
        [stateField]: true
      },
      () => {
        setTimeout(() => {
          node.setState({
            [stateField]: false
          });
        }, 1000);
      }
    );
  }

  __handleSubmit = () => {
    if (!this.__formIsValid()) return;
    investmentsActions
      .withdrawAdd(this.__buildParams())
      .then(info => {
        this.setState({ success: true });
      })
      .catch(err => {
        toasts.error(err.message);
      });
  };
}

export default connect(stage => ({
  gaEnabled: stage.default.profile.ga_enabled
}))(WithdrawalModal);
