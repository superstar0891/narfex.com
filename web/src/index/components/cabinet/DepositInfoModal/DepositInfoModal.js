import "./DepositInfoModal.less";

import React from "react";
import * as UI from "../../../../ui";

import InfoRow, { InfoRowGroup } from "../../cabinet/InfoRow/InfoRow";
import * as utils from "../../../../utils";
import { getDeposit } from "../../../../actions/cabinet/investments";
import * as actions from "../../../../actions";
import ModalState from "../ModalState/ModalState";
// import Table, { TableCell, TableColumn } from 'src/ui/components/Table/Table.js';

export default class DepositInfoModal extends React.Component {
  state = {
    deposit: null,
    status: "loading"
  };

  load = () => {
    getDeposit(this.props.depositId)
      .then(deposit => {
        this.setState({ deposit, status: "" });
      })
      .catch(error => {
        this.setState({ status: "failed" });
      });
  };

  handleWithdraw = () => {
    const { deposit } = this.state;
    actions.openModal(
      "deposit_withdraw",
      { depositId: deposit.id },
      { deposit }
    );
  };

  componentDidMount() {
    this.load();
  }

  render() {
    const { deposit, status } = this.state;

    if (status) {
      return <ModalState status={status} onRetry={this.__load} />;
    }

    const currency = deposit.currency.toUpperCase();
    const currencyInfo = actions.getCurrencyInfo(currency);

    const isPool = deposit.type === "pool";

    return (
      <UI.Modal
        noSpacing
        className="DepositInfoModal__wrapper"
        isOpen={true}
        onClose={this.props.onClose}
      >
        <UI.ModalHeader>
          {isPool ? (
            utils.getLang("cabinet_detailsInvestmentPoolTitle")
          ) : (
            <span>
              {utils.getLang("cabinet_depositInfoModal_deposit")}{" "}
              {deposit.plan_percent}% {deposit.description}
            </span>
          )}
        </UI.ModalHeader>
        <div className="DepositInfoModal__cont">
          {/*{ deposit.can_withdraw && <UI.WalletCard*/}
          {/*  balance={deposit.can_withdraw_amount}*/}
          {/*  title={utils.getLang('cabinet_investmentsAvailableWithdrawal')}*/}
          {/*  currency={currencyInfo}*/}
          {/*/> }*/}

          <UI.CircleIcon
            className="DepositInfoModal__icon"
            currency={currencyInfo}
          />

          <div className="DepositInfoModal__columns">
            <InfoRowGroup className="DepositInfoModal__column">
              {/*<InfoRow label="ID">{deposit.localId}</InfoRow>*/}
              <InfoRow label={utils.getLang("global_type")}>
                {utils.ucfirst(deposit.type)}
              </InfoRow>
              <InfoRow label={utils.getLang("global_status")}>
                {utils.ucfirst(deposit.status)}
              </InfoRow>
              <InfoRow label={utils.getLang("created")}>
                {utils.dateFormat(deposit.created_at)}
              </InfoRow>
              <InfoRow label={utils.getLang("period")}>
                {deposit.passed_days} / {deposit.days}{" "}
                {utils.getLang("cabinet_openNewDeposit_days")}
              </InfoRow>
            </InfoRowGroup>
            <InfoRowGroup className="DepositInfoModal__column">
              {!isPool && (
                <InfoRow label={utils.getLang("global_invested")}>
                  {deposit.amount} {currency}
                </InfoRow>
              )}
              {isPool && (
                <InfoRow
                  label={utils.getLang("cabinet_depositModalProposedAmount")}
                >
                  <UI.NumberFormat
                    number={deposit.proposed_amount}
                    currency={currency}
                  />
                </InfoRow>
              )}
              {isPool && (
                <InfoRow label={utils.getLang("cabinet_depositModalAmount")}>
                  {deposit.amount} {currency}
                </InfoRow>
              )}
              <InfoRow
                label={utils.getLang("cabinet_investmentsScreen_profit")}
              >
                <UI.NumberFormat number={deposit.profit} currency={currency} />{" "}
                <UI.NumberFormat
                  number={deposit.current_percent}
                  brackets
                  percent
                />
              </InfoRow>
              <InfoRow label={utils.getLang("in_fiat")}>
                ~<UI.NumberFormat number={deposit.usd_profit} currency="usd" />
              </InfoRow>
              {!isPool && (
                <InfoRow label={utils.getLang("global_estimated")}>
                  <UI.NumberFormat
                    number={(deposit.amount / 100) * deposit.percent}
                    currency={currency}
                  />{" "}
                  <UI.NumberFormat number={deposit.percent} brackets percent />
                </InfoRow>
              )}
            </InfoRowGroup>
          </div>

          {/*{ deposit.can_withdraw && <div className="DepositInfoModal__withdrawAction">*/}
          {/*  <UI.Button*/}
          {/*    disabled={!deposit.can_withdraw_amount}*/}
          {/*    onClick={this.handleWithdraw}*/}
          {/*    currency={currencyInfo}*/}
          {/*  >{utils.getLang('global_withdrawAction')}</UI.Button>*/}
          {/*</div> }*/}

          {/*<Table className="DepositInfoModal__withdrawalHistory" skipContentBox header={utils.getLang('cabinet_investmentsWithdrawalHistory')} headings={[*/}
          {/*  <TableColumn>{utils.getLang('global_amount')}</TableColumn>,*/}
          {/*  <TableColumn>{utils.getLang('cabinet_investmentsIncomeReduction')}</TableColumn>,*/}
          {/*  <TableColumn>{utils.getLang('global_date')}</TableColumn>,*/}
          {/*]}>*/}
          {/*  {[1,2,3,4,5].map(i => (*/}
          {/*    <TableCell>*/}
          {/*      <TableColumn><UI.NumberFormat number={22} currency="usd" /></TableColumn>*/}
          {/*      <TableColumn><UI.NumberFormat number={i} percent /></TableColumn>*/}
          {/*      <TableColumn>{utils.dateFormat(1000 * i)}</TableColumn>*/}
          {/*    </TableCell>*/}
          {/*  ))}*/}
          {/*</Table>*/}
        </div>
      </UI.Modal>
    );
  }
}
