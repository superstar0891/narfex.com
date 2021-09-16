import "./FiatRefillModal.less";

import React, { useState, useEffect } from "react";
import { connect } from "react-redux";
import * as firebase from "firebase";

import Modal, { ModalHeader } from "../../../../ui/components/Modal/Modal";
import NumberFormat from "../../../../ui/components/NumberFormat/NumberFormat";
import BankList from "./components/BankList/BankList";
import MethodsList from "./components/MethodsList/MethodsList";
import { refillBanksGet } from "../../../../actions/cabinet/fiat";
import LoadingStatus from "../LoadingStatus/LoadingStatus";
import BankLogo from "../../../../ui/components/BankLogo/BankLogo";
import Clipboard from "src/index/components/cabinet/Clipboard/Clipboard";
import Button, { ButtonWrapper } from "../../../../ui/components/Button/Button";
import { getLang } from "../../../../utils";

const WithdrawalRefillModal = props => {
  const { amount, balance, adaptive, bankList, percentFee, minFee } = props;
  const [bank, changeBank] = useState(null);
  const fee = Math.max((amount / 100) * percentFee, minFee);

  useEffect(() => {
    props.refillBanksGet();

    if (!amount || !balance) {
      props.onClose();
    }

    firebase.analytics().logEvent("open_fiat_refill_modal");
    // eslint-disable-next-line
  }, [firebase]);

  if (!amount || !balance) {
    return null;
  }

  return (
    <Modal noSpacing isOpen={true} onClose={props.onClose}>
      {adaptive && (
        <ModalHeader>
          {getLang("cabinet_fiatWithdrawalModal_chooseBank")}
        </ModalHeader>
      )}
      <div className="FiatRefillModal">
        <div className="FiatRefillModal__sideBar">
          <div className="FiatRefillModal__header">
            {getLang("cabinet_balanceDeposit")}
          </div>
          <div className="FiatRefillModal__sideBar__content">
            <div className="FiatRefillModal__sideBar__amount">
              <small>{getLang("global_amount")}</small>
              <strong>
                <NumberFormat number={amount} currency={balance.currency} />
              </strong>
            </div>
            <div className="FiatRefillModal__sideBar__fee">
              <small>{getLang("global_fee")}</small>
              <strong>
                <NumberFormat number={fee} currency={balance.currency} />
              </strong>
            </div>
            <hr />
            <div className="FiatRefillModal__sideBar__amount">
              <small>{getLang("cabinet_fiatRefillModal_total")}</small>
              <strong>
                <NumberFormat
                  number={amount - fee}
                  currency={balance.currency}
                />
              </strong>
            </div>
            {/*<div className="FiatRefillModal__sideBar__fee">*/}
            {/*  <small>*/}
            {/*    <NumberFormat number={amount} currency={balance.currency} />*/}
            {/*  </small>*/}
            {/*  <small>*/}
            {/*    {getLang("global_fee")}:{" "}*/}
            {/*    <NumberFormat number={fee} currency={balance.currency} />*/}
            {/*  </small>*/}
            {/*</div>*/}
            {/*<div className="FiatRefillModal__sideBar__total">*/}
            {/*  <h2>{getLang("global_total")}</h2>*/}
            {/*  <h2>*/}
            {/*    <NumberFormat number={total} currency={balance.currency} />*/}
            {/*  </h2>*/}
            {/*  <small>*/}
            {/*    {getLang("cabinet_fiatWithdrawalModal_estimatedAt")}{" "}*/}
            {/*    <NumberFormat number={amountUsd} currency="usd" />*/}
            {/*  </small>*/}
            {/*</div>*/}
          </div>
        </div>
        <div className="FiatRefillModal__body">
          {!bank ? (
            <>
              <div className="FiatRefillModal__header">
                {getLang("cabinet_fiatWithdrawalModal_chooseBank")}
              </div>
              {bankList && !props.loadingStatus ? (
                <BankList onChange={changeBank} items={bankList} />
              ) : (
                <LoadingStatus status={props.loadingStatus} />
              )}
              <ButtonWrapper
                align="right"
                className="FiatRefillModal__body__footer"
              >
                <Button onClick={props.onBack} type="secondary">
                  {getLang("global_back")}
                </Button>
              </ButtonWrapper>
            </>
          ) : (
            <>
              <div className="FiatRefillModal__body__content">
                <div className="FiatRefillModal__header">{bank.name}</div>
                <p>
                  <BankLogo name={bank.code} />
                </p>
                <ul className="FiatRefillModal__accountInfo">
                  <li>
                    {getLang("cabinet_virtualAccount")} #{" "}
                    <span>
                      <Clipboard text={bank.account_number} />
                    </span>
                  </li>
                </ul>
                <p>{getLang("cabinet_fiatWithdrawalModal__infoText")}</p>

                {/*<pre>{JSON.stringify(bank.methods, null, 2)}</pre>*/}
                <MethodsList
                  keys={{
                    account_number: bank.account_number,
                    service_provider_code: bank.service_provider_code
                  }}
                  methods={bank.methods}
                />
              </div>

              <ButtonWrapper
                align="right"
                className="FiatRefillModal__body__footer"
              >
                <Button
                  onClick={() => {
                    changeBank(null);
                  }}
                  type="secondary"
                >
                  {getLang("global_back")}
                </Button>
              </ButtonWrapper>
            </>
          )}
        </div>
      </div>
    </Modal>
  );
};

export default connect(
  state => ({
    accountName: [
      state.default.profile.user.first_name,
      state.default.profile.user.last_name
    ].join(" "),
    adaptive: state.default.adaptive,
    loadingStatus: state.fiat.loadingStatus.refillBankList,
    withdrawalStatus: state.fiat.loadingStatus.withdrawal,
    bankList: state.fiat.refillBankList
  }),
  {
    refillBanksGet: refillBanksGet
  }
)(WithdrawalRefillModal);
