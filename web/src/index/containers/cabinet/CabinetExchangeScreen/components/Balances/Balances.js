import "./Balances.less";

import React, { memo } from "react";
import { connect } from "react-redux";

import * as UI from "../../../../../../ui";
import * as utils from "../../../../../../utils";
import Block from "../Block/Block";
import { openModal } from "../../../../../../actions/";
import EmptyContentBlock from "../../../../../components/cabinet/EmptyContentBlock/EmptyContentBlock";

class Balances extends React.Component {
  shouldComponentUpdate(nextProps) {
    return (
      nextProps.adaptive !== this.props.adaptive ||
      nextProps.balances !== this.props.balances ||
      nextProps.currentLang !== this.props.currentLang
    );
  }

  __handleOpenBalance() {
    openModal("manage_balance");
  }

  get isEmpty() {
    return !this.props.balances.filter(balance => balance.amount).length;
  }

  renderContent() {
    if (this.isEmpty) {
      return (
        <EmptyContentBlock
          skipContentClass
          icon={require("src/asset/120/wallet.svg")}
          message={utils.getLang("exchange_emptyBalance")}
          button={{
            text: utils.getLang("cabinet_manage"),
            onClick: this.__handleOpenBalance,
            size: "small"
          }}
        />
      );
    }

    const headings = [
      <UI.TableColumn>{utils.getLang("global_currency")}</UI.TableColumn>,
      <UI.TableColumn align="right">
        {utils.getLang("global_amount")}
      </UI.TableColumn>
    ];

    let rows = this.props.balances.map(balance => {
      return (
        <UI.TableCell key={balance.id}>
          <UI.TableColumn>{balance.currency.toUpperCase()}</UI.TableColumn>
          <UI.TableColumn align="right">
            <UI.NumberFormat
              market
              number={balance.amount}
              currency={balance.currency}
              hiddenCurrency
            />
          </UI.TableColumn>
        </UI.TableCell>
      );
    });

    return (
      <UI.Table headings={headings} compact skipContentBox>
        {rows}
      </UI.Table>
    );
  }

  render() {
    return this.props.adaptive ? (
      <div className="Exchange__balance">
        {this.renderContent()}
        {!this.isEmpty && (
          <UI.Button onClick={this.__handleOpenBalance}>
            {utils.getLang("cabinet_manage")}
          </UI.Button>
        )}
      </div>
    ) : (
      <Block
        className="Exchange__balance"
        name="balance"
        title={utils.getLang("global_balance")}
        controls={
          !this.isEmpty
            ? [
                <UI.Button
                  key="withdraw"
                  size="ultra_small"
                  rounded
                  type="secondary"
                  onClick={this.__handleOpenBalance}
                >
                  {utils.getLang("cabinet_manage")}
                </UI.Button>
              ]
            : null
        }
      >
        {this.renderContent()}
      </Block>
    );
  }
}

export default connect(
  state => ({
    ...state.exchange,
    currentLang: state.default.currentLang
  }),
  {}
)(memo(Balances));
