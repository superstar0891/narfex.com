import "./OperationModal.less";

import React from "react";

import Modal from "../../../../ui/components/Modal/Modal";
import * as UI from "../../../../ui";

import SwapOperation from "./operations/Swap";
import UserAuthorizeOperation from "./operations/UserAuthorize";
import WithdrawalOperation from "./operations/Withdrawal";
import TransactionSendOperation from "./operations/TransactionSend";
import TransferSendOperation from "./operations/TransferSend";
import TransferReceiveOperation from "./operations/TransferReceive";
import RefillOperation from "./operations/Refill";
import TransactionReceiveOperation from "./operations/TransactionReceive";
import BankCardRefillRejectOperation from "./operations/BankCardRefillReject";
import SavingAccrualOperation from "./operations/SavingAccrual";
import InternalTransactionOperation from "./operations/InternalTransaction";
import PromoCodeRewardOperation from "./operations/PromoCodeReward";
import Lang from "../../../../components/Lang/Lang";
import { Message } from "../../../../ui";

export default props => {
  const { operation } = props;

  if (!operation) {
    props.onClose();
    return null;
  }

  const renderContent = operation => {
    if (operation.type === "swap" || operation.type === "buy_token") {
      return <SwapOperation operation={operation} />;
    } else if (operation.type === "user_authorize") {
      return <UserAuthorizeOperation operation={operation} />;
    } else if (operation.type === "withdrawal") {
      return <WithdrawalOperation operation={operation} />;
    } else if (operation.type === "transaction_send") {
      return <TransactionSendOperation operation={operation} />;
    } else if (operation.type === "transfer_send") {
      return <TransferSendOperation operation={operation} />;
    } else if (operation.type === "transfer_receive") {
      return <TransferReceiveOperation operation={operation} />;
    } else if (operation.type === "refill") {
      return <RefillOperation operation={operation} />;
    } else if (operation.type === "transaction_receive") {
      return <TransactionReceiveOperation operation={operation} />;
    } else if (operation.type === "saving_accrual") {
      return <SavingAccrualOperation operation={operation} />;
    } else if (operation.type === "internal_transaction") {
      return <InternalTransactionOperation operation={operation} />;
    } else if (operation.type === "bank_card_refill_reject") {
      return <BankCardRefillRejectOperation operation={operation} />;
    } else if (operation.type === "promo_code_reward") {
      return <PromoCodeRewardOperation operation={operation} />;
    } else {
      return (
        <div>
          <Message type="error">Error "{operation.type}" type</Message>
        </div>
      );
    }
  };

  return (
    <Modal className="OperationModal" isOpen={true} onClose={props.onClose}>
      <UI.ModalHeader>
        <Lang name={"cabinet__historyItemTitle_" + operation.type} />
      </UI.ModalHeader>
      {/*<UI.Code>{JSON.stringify(operation, null, 2)}</UI.Code>*/}
      {renderContent(operation)}
    </Modal>
  );
};
