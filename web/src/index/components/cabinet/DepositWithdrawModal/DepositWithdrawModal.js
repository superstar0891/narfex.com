import "./DepositWithdrawModal.less";

import React, { useState, useEffect, useRef } from "react";
import { connect } from "react-redux";
import SVG from "react-inlinesvg";

import { getLang, throttle } from "src/utils";
import Modal, { ModalHeader } from "src/ui/components/Modal/Modal";
import Input from "src/ui/components/Input/Input";
import Button from "src/ui/components/Button/Button";
import ModalState from "../ModalState/ModalState";
import NumberFormat from "src/ui/components/NumberFormat/NumberFormat";
import LoadingStatus from "src/index/components/cabinet/LoadingStatus/LoadingStatus";
import { getDeposit } from "../../../../actions/cabinet/investments";
import * as actions from "../../../../actions";
import * as toasts from "../../../../actions/toasts";
import * as utils from "src/utils/index";
import {
  depositCalculate,
  depositWithdraw
} from "../../../../actions/cabinet/investments";

const DepositWithdrawModal = props => {
  const [gaCode, changeGaCode] = useState("");
  const [gaCodeError, setGaError] = useState(false);
  const [gaAmountError, setAmountError] = useState(false);
  const [deposit, setDeposit] = useState(props.deposit || false);
  const [amount, setAmount] = useState("");
  const [calcPending, setCalcPending] = useState(false);
  const [submitPending, setSubmitPending] = useState(false);
  const [calculation, setCalculation] = useState(null);

  const handleGaError = () => {
    setGaError(true);
    setTimeout(() => setGaError(false), 2000);
  };

  const handleAmountError = () => {
    setAmountError(true);
    setTimeout(() => setAmountError(false), 2000);
  };

  useEffect(() => {
    !deposit && getDeposit(props.depositId).then(setDeposit);
  }, [deposit, props.depositId]);

  const handleDepositCalculate = (deposit, amount) => {
    if (amount > 0 && amount <= deposit.can_withdraw_amount) {
      setCalcPending(true);
      depositCalculate(deposit.id, amount)
        .then(setCalculation)
        .finally(() => {
          setCalcPending(false);
        });
    } else {
      setCalcPending(false);
    }
  };

  const handleDepositCalculateThrottled = useRef(
    throttle(handleDepositCalculate, 500)
  ).current;

  const handleSubmit = () => {
    if (!gaCode) {
      handleGaError();
      return false;
    }
    setSubmitPending(true);
    depositWithdraw({
      deposit_id: deposit.id,
      ga_code: gaCode,
      amount
    })
      .then(() => {
        toasts.success(getLang("cabinet_investmentsDepositWithdrawalSuccess"));
        props.onClose();
      })
      .catch(error => {
        toasts.error(error.message);
        error.code = "ga_auth_code_incorrect" && handleGaError();
        error.code = "amount_incorrect" && handleAmountError();
      })
      .finally(() => {
        setSubmitPending(false);
      });
  };

  const handleChangeAmount = amount => {
    setAmount(amount);
    handleDepositCalculateThrottled(deposit, amount);
  };

  const handleClickMax = () => {
    const amount = utils.formatDouble(deposit.can_withdraw_amount, 8);
    setAmount(amount);
    handleDepositCalculate(deposit);
  };

  if (!deposit) {
    return <ModalState status="loading" />;
  }

  if (!deposit.can_withdraw) {
    props.onClose();
    return null;
  }

  const currencyInfo = actions.getCurrencyInfo(deposit.currency);

  const data = {
    initial_profit: (deposit.amount / 100) * deposit.percent,
    initial_percent: deposit.percent,
    ...calculation
  };

  const renderInitialProfit = () => (
    <div className="DepositWithdrawModal__row">
      <div className="DepositWithdrawModal__label">
        {getLang("cabinet_investmentsIncomeWithoutwithdrawal")}:
      </div>
      <span className="DepositWithdrawModal__amount">
        <NumberFormat number={data.initial_profit} currency="eth" />{" "}
        <NumberFormat
          type="up"
          number={data.initial_percent}
          percent
          brackets
        />
      </span>
    </div>
  );

  const renderResult = () => {
    if (calcPending) {
      return <LoadingStatus inline status="loading" />;
    }

    if (
      !calcPending &&
      data.drop_amount &&
      amount > 0 &&
      amount <= deposit.can_withdraw_amount
    ) {
      return (
        <>
          <div className="DepositWithdrawModal__row">
            <div className="DepositWithdrawModal__label">
              {getLang("cabinet_investmentsDropProfit")}:
            </div>
            <span className="DepositWithdrawModal__amount">
              <NumberFormat number={-data.drop_amount} currency="eth" />{" "}
              <NumberFormat
                type="down"
                number={-data.drop_percent}
                percent
                brackets
              />
            </span>
          </div>
          <div className="DepositWithdrawModal__row">
            <div className="DepositWithdrawModal__label">
              {getLang("cabinet_investmentsResultProfit")}:
            </div>
            <span className="DepositWithdrawModal__amount">
              <NumberFormat number={data.result_profit} currency="eth" />{" "}
              <NumberFormat
                type="down"
                number={data.result_percent}
                percent
                brackets
              />
            </span>
          </div>
        </>
      );
    }
  };

  return (
    <Modal
      className="DepositWithdrawModal"
      isOpen={true}
      onClose={props.onClose}
    >
      <ModalHeader>
        {getLang("cabinet_investmentsWithdrawProfitTitle")}
      </ModalHeader>
      <div
        className="DepositWithdrawModal__icon"
        style={{ backgroundImage: `url(${currencyInfo.icon})` }}
      />
      <div className="DepositWithdrawModal__content">
        {/*<pre>{JSON.stringify(deposit, null, 2)}</pre>*/}
        <div className="DepositWithdrawModal__coll">
          {props.adaptive && renderInitialProfit()}
          <div className="DepositWithdrawModal__row amount">
            <Input
              autoFocus={true}
              value={amount}
              type="number"
              error={gaAmountError || amount > deposit.can_withdraw_amount}
              onTextChange={handleChangeAmount}
              indicator="ETH"
              placeholder="Сумма Вывода"
            />
            <Button onClick={handleClickMax} type="secondary">
              {getLang("cabinet_withdrawalModal_max")}
            </Button>
          </div>
          {props.adaptive && renderResult()}
          <div className="DepositWithdrawModal__row">
            <Input
              type="code"
              cell
              error={gaCodeError}
              onTextChange={changeGaCode}
              mouseWheel={false}
              autoComplete="off"
              value={gaCode}
              maxLength={6}
              placeholder={getLang("site__authModalGAPlaceholder", true)}
              indicator={
                <SVG src={require("../../../../asset/google_auth.svg")} />
              }
            />
          </div>
          <div className="DepositWithdrawModal__row">
            <Button
              disabled={
                !(
                  !calcPending &&
                  amount > 0 &&
                  amount <= deposit.can_withdraw_amount
                )
              }
              state={submitPending && "loading"}
              onClick={handleSubmit}
            >
              {getLang("global_confirm")}
            </Button>
          </div>
        </div>
        <div className="DepositWithdrawModal__coll info">
          {!props.adaptive && renderInitialProfit()}
          {!props.adaptive && renderResult()}
        </div>
      </div>
    </Modal>
  );
};

export default connect(state => ({
  adaptive: state.default.adaptive
}))(DepositWithdrawModal);
