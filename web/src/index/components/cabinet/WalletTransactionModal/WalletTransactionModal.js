import "./WalletTransactionModal.less";

import React from "react";
import * as UI from "../../../../ui";

import * as actions from "../../../../actions";
import * as walletsActions from "../../../../actions/cabinet/wallets";
import LoadingStatus from "../../cabinet/LoadingStatus/LoadingStatus";
import * as utils from "../../../../utils";
import InfoRow, { InfoRowGroup } from "../../cabinet/InfoRow/InfoRow";

export default class VerificationModalWalletTransactionModal extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      loadingStatus: "loading",
      info: false
    };
  }

  componentDidMount() {
    this.__load();
  }

  render() {
    return (
      <UI.Modal
        className="WalletTransactionModal__wrapper"
        isOpen={true}
        onClose={this.props.onClose}
        width={464}
      >
        <UI.ModalHeader>{this.__getTitle()}</UI.ModalHeader>
        {this.__renderContent()}
      </UI.Modal>
    );
  }

  __getTitle() {
    if (!this.state.info) {
      return utils.getLang("cabinet_modal_loadingText");
    }

    const currencyInfo = actions.getCurrencyInfo(this.state.info.currency);

    return this.state.info.type.includes("send") ? (
      <span>
        {utils.getLang("cabinet_wallets_historyTable_sent")}{" "}
        {utils.ucfirst(currencyInfo.name)}
      </span>
    ) : (
      <span>
        {utils.getLang("cabinet_wallets_historyTable_received")}{" "}
        {utils.ucfirst(currencyInfo.name)}
      </span>
    );
  }

  __renderContent() {
    if (this.state.loadingStatus) {
      return <LoadingStatus inline status={this.state.loadingStatus} />;
    } else {
      const data = this.state.info;
      const currencyInfo = actions.getCurrencyInfo(data.currency);
      const currency = data.currency.toUpperCase();
      let address = data.address;
      if (address && this.props.type.includes("transfer")) {
        address = address.toUpperCase();
      }

      const status = {
        done: utils.getLang("cabinet_walletTransactionModal_confirmed"),
        pending: utils.getLang("cabinet_walletTransactionModal_confirmation"),
        canceled: utils.getLang("cabinet_walletTransactionModal_canceled")
      }[data.status];

      return (
        <div>
          <UI.CircleIcon
            className="WalletTransactionModal__icon"
            currency={currencyInfo}
          />
          <InfoRowGroup align="left">
            <InfoRow label={utils.getLang("global_from")}>
              {this.state.info.type.includes("receive") ? (
                <div className="Wallets__history__address">
                  <UI.WalletAddress
                    isUser={this.state.info.type.includes("transfer")}
                    address={
                      address ||
                      utils.getLang("cabinet_wallets_historyTable_unknown")
                    }
                  />
                </div>
              ) : (
                <>
                  {utils.getLang("cabinet_walletTransactionModal_my")}{" "}
                  {utils.ucfirst(currencyInfo.name)}{" "}
                  {utils.getLang("global_wallet")}
                </>
              )}
            </InfoRow>
            <InfoRow label={utils.getLang("global_to")}>
              {this.state.info.type.includes("receive") ? (
                <>
                  {utils.getLang("cabinet_walletTransactionModal_my")}{" "}
                  {utils.ucfirst(currencyInfo.name)}{" "}
                  {utils.getLang("global_wallet")}
                </>
              ) : (
                <div className="Wallets__history__address">
                  <UI.WalletAddress
                    isUser={this.state.info.type.includes("transfer")}
                    address={address}
                  />
                </div>
              )}
            </InfoRow>
            <InfoRow label={utils.getLang("global_amount")}>
              <UI.NumberFormat number={data.amount} currency={currency} />
            </InfoRow>
            {data.txid && (
              <InfoRow label={utils.getLang("global_txid")}>
                <div className="Wallets__history__address">{data.txid}</div>
              </InfoRow>
            )}
            <InfoRow label={utils.getLang("global_fee")}>
              <UI.NumberFormat number={data.fee || 0} currency={currency} />
            </InfoRow>
            <InfoRow label={utils.getLang("global_date")}>
              {utils.dateFormat(data.created_at)}
            </InfoRow>
          </InfoRowGroup>

          <UI.WalletCard
            title={utils.getLang("cabinet_walletTransactionModal_total")}
            balance={data.amount + (data.fee || 0)}
            currency={currencyInfo.abbr}
          />

          <div className="WalletTransactionModal__status">
            {data.required_confirmations && (
              <div className="WalletTransactionModal__status__row">
                <div className="WalletTransactionModal__status__row__label">
                  {utils.getLang(
                    "cabinet_walletTransactionModal_blockchainConfirmations"
                  )}
                </div>
                <div className="WalletTransactionModal__status__row__value">
                  {data.confirmations}/{data.required_confirmations}
                </div>
              </div>
            )}
            <div className="WalletTransactionModal__status__row right">
              <div className="WalletTransactionModal__status__row__label">
                {utils.getLang("cabinet_walletTransactionModal_status")}
              </div>
              <div
                className={utils.classNames({
                  WalletTransactionModal__status__row__value: true,
                  [data.status]: data.status
                })}
              >
                {status}
              </div>
            </div>
          </div>
        </div>
      );
    }
  }

  __load = () => {
    this.setState({ loadingStatus: "loading" });
    walletsActions
      .loadTransactionInfo(this.props.id, this.props.type)
      .then(info => {
        this.setState({ loadingStatus: "", info });
      })
      .catch(() => {
        this.setState({ loadingStatus: "failed" });
      });
  };
}
