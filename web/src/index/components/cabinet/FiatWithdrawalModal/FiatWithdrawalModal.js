import "./FiatWithdrawalModal.less";

import React, { useState, useEffect } from "react";
import { connect } from "react-redux";

import Modal, { ModalHeader } from "../../../../ui/components/Modal/Modal";
import NumberFormat from "../../../../ui/components/NumberFormat/NumberFormat";
import BankList from "./components/BankList/BankList";
import {
  withdrawalBanksGet,
  fiatWithdrawal
} from "../../../../actions/cabinet/fiat";
import LoadingStatus from "../LoadingStatus/LoadingStatus";
import BankLogo from "../../../../ui/components/BankLogo/BankLogo";
import Input from "../../../../ui/components/Input/Input";
import Button, { ButtonWrapper } from "../../../../ui/components/Button/Button";
import Form from "../../../../ui/components/Form/Form";
import { getLang } from "../../../../utils";
import Lang from "../../../../components/Lang/Lang";
import * as actions from "../../../../actions";

const FiatWithdrawalModal = props => {
  const { amount, balance, adaptive, bankList, minFee, percentFee } = props;
  const [bank, changeBank] = useState(null);
  const [accountHolderName, setAccountHolderName] = useState("");
  const [accountNumber, setAccountNumber] = useState("");
  const [touched, touch] = useState(false);
  const [filled, fill] = useState(false);
  const fee = Math.max((amount / 100) * percentFee, minFee);

  useEffect(() => {
    props.withdrawalBanksGet();

    if (!amount || !balance) {
      // props.onClose();
    }
    // eslint-disable-next-line
  }, [amount, balance]);

  if (!amount || !balance) {
    return null;
  }

  const handleNext = () => {
    touch(true);
    if (accountHolderName) {
      fill(true);
    }
  };

  const handleSubmit = () => {
    actions.gaCode().then(gaCode => {
      props.fiatWithdrawal({
        bank,
        accountHolderName,
        accountNumber,
        amount,
        balance,
        gaCode
      });
    });
  };

  const headerText = !bank ? (
    getLang("cabinet_fiatWithdrawalModal_chooseBank")
  ) : !filled ? (
    <span>
      {getLang("cabinet_fiatWithdrawalModal__toBankAccount")} {bank.name}
    </span>
  ) : (
    getLang("cabinet_fiatWithdrawalModal_confirmWithdrawal")
  );

  return (
    <Modal noSpacing isOpen={true} onClose={props.onClose}>
      {adaptive && <ModalHeader>{headerText}</ModalHeader>}
      <div className="FiatWithdrawalModal">
        <div className="FiatWithdrawalModal__sideBar">
          <div className="FiatWithdrawalModal__header">
            {getLang("cabinet_fiatWithdrawalModal_title")}
          </div>
          <div className="FiatWithdrawalModal__sideBar__content">
            <div className="FiatWithdrawalModal__sideBar__amount">
              <small>
                <Lang name="global_amount" />
              </small>
              <strong>
                <NumberFormat number={amount} currency={balance.currency} />
              </strong>
            </div>
            <div className="FiatWithdrawalModal__sideBar__fee">
              <small>
                <Lang name="global_fee" />
              </small>
              <strong>
                <NumberFormat number={fee} currency={balance.currency} />
              </strong>
            </div>
            <hr />
            <div className="FiatWithdrawalModal__sideBar__amount">
              <small>
                <Lang name="cabinet_fiatWithdrawalModal_total" />
              </small>
              <strong>
                <NumberFormat
                  number={amount + fee}
                  currency={balance.currency}
                />
              </strong>
            </div>
          </div>
        </div>
        <div className="FiatWithdrawalModal__body">
          {!bank ? (
            <>
              {bankList && !props.loadingStatus ? (
                <>
                  <div className="FiatWithdrawalModal__body__content">
                    <div className="FiatWithdrawalModal__header">
                      {headerText}
                    </div>
                    <BankList onChange={changeBank} items={bankList} />
                  </div>
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
                <LoadingStatus status={props.loadingStatus} />
              )}
            </>
          ) : !filled ? (
            <>
              <div className="FiatWithdrawalModal__body__content">
                <div className="FiatWithdrawalModal__header">{headerText}</div>

                <p>
                  <BankLogo name={bank.code} />
                </p>

                <p>{getLang("cabinet_fiatWithdrawalModal__infoText")}</p>

                {/*<Message title={getLang('global_attention')} type="error">{getLang('cabinet_fiatWithdrawalModal__warningText')}</Message>*/}

                {/*<p className="FiatWithdrawalModal__accountName">*/}
                {/*  Account Name: <span onClick={() => setAccountName(props.accountName)}>{props.accountName}</span>*/}
                {/*</p>*/}

                <Form>
                  <Input
                    error={touched && !accountHolderName}
                    value={accountHolderName}
                    onTextChange={setAccountHolderName}
                    placeholder={getLang(
                      "cabinet_fiatWithdrawalModal__accountHolderName",
                      true
                    )}
                  />
                  <Input
                    error={touched && !accountNumber}
                    value={accountNumber}
                    onTextChange={setAccountNumber}
                    placeholder={getLang(
                      "cabinet_fiatWithdrawalModal__accountNumber",
                      true
                    )}
                    type="number"
                  />
                </Form>
              </div>

              <ButtonWrapper
                align="right"
                className="FiatWithdrawalModal__body__footer"
              >
                <Button
                  onClick={() => {
                    changeBank(null);
                    touch(false);
                  }}
                  type="secondary"
                >
                  {getLang("global_back")}
                </Button>
                <Button onClick={handleNext}>{getLang("global_next")}</Button>
              </ButtonWrapper>
            </>
          ) : (
            <>
              <div className="FiatWithdrawalModal__body__content">
                <div className="FiatWithdrawalModal__header">{headerText}</div>
                <p>
                  <BankLogo name={bank.code} />
                </p>
                <ul className="FiatWithdrawalModal__dataList">
                  <li>
                    {getLang("global_bank")}:{" "}
                    <span className="value">{bank.name}</span>
                  </li>
                  <li>
                    {getLang("global_amount")}:{" "}
                    <span className="value">
                      <NumberFormat number={amount} currency="idr" />
                    </span>
                  </li>
                  <li>
                    {getLang("cabinet_fiatWithdrawalModal__accountHolderName")}:{" "}
                    <span className="value">{accountHolderName}</span>
                  </li>
                  <li>
                    {getLang("cabinet_fiatWithdrawalModal__accountNumber")}:{" "}
                    <span className="value">{accountNumber}</span>
                  </li>
                </ul>
              </div>
              <ButtonWrapper
                align="right"
                className="FiatWithdrawalModal__body__footer"
              >
                <Button onClick={() => fill(false)} type="secondary">
                  {getLang("global_back")}
                </Button>
                <Button state={props.withdrawalStatus} onClick={handleSubmit}>
                  {getLang("global_confirm")}
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
    loadingStatus: state.fiat.loadingStatus.withdrawalBankList,
    withdrawalStatus: state.fiat.loadingStatus.withdrawal,
    bankList: state.fiat.withdrawalBankList
  }),
  {
    withdrawalBanksGet,
    fiatWithdrawal
  }
)(FiatWithdrawalModal);
