import "./PartnersWithdrawBalanceModal.less";

import React, { useState, useCallback } from "react";
import { useDispatch, useSelector } from "react-redux";
import {
  Button,
  ButtonWrapper,
  Input,
  Modal,
  ModalHeader,
  NumberFormat
} from "../../../../ui";
import { getLang } from "../../../../utils";
import Lang from "../../../../components/Lang/Lang";
import {
  partnersBalanceSelector,
  partnersStatusSelector
} from "../../../../selectors";
import { partnersBalanceWithdrawal } from "../../../../actions/cabinet/partners";

export default ({ balanceId, onClose }) => {
  const dispatch = useDispatch();
  const balance = useSelector(partnersBalanceSelector(balanceId));
  const status = useSelector(partnersStatusSelector("withdrawal"));
  const [amount, setAmount] = useState("");

  const handleSubmit = useCallback(() => {
    dispatch(partnersBalanceWithdrawal(balanceId, amount));
  }, [dispatch, balanceId, amount]);

  const handleClickMax = useCallback(() => {
    setAmount(balance.amount);
  }, [balance]);

  return (
    <Modal onClose={onClose} className="PartnersWithdrawBalanceModal">
      <ModalHeader>
        <Lang name="cabinet_partnersWithdrawalBalanceModal_title" />
      </ModalHeader>
      <div className="PartnersWithdrawBalanceModal__content">
        <div className="PartnersWithdrawBalanceModal__label">
          <Lang name="global_available" />:
        </div>

        <div className="PartnersWithdrawBalanceModal__amount">
          <NumberFormat number={balance.amount} currency={balance.currency} />
        </div>

        <div className="PartnersWithdrawBalanceModal__row">
          <Input
            value={amount}
            onTextChange={setAmount}
            placeholder={getLang("global_amount", true)}
            indicator="BTC"
          />
          <Button onClick={handleClickMax} type="secondary">
            <Lang name="global_max" />
          </Button>
        </div>

        <ButtonWrapper align="center">
          <Button state={status} onClick={handleSubmit}>
            <Lang name="cabinet_partnersWithdrawalBalanceModal_withdraw" />
          </Button>
        </ButtonWrapper>
      </div>
    </Modal>
  );
};
