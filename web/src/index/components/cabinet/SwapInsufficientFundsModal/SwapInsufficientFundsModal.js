import "./SwapInsufficientFunds.less";

import React, { useCallback } from "react";
import { useSelector } from "react-redux";
import {
  Button,
  ButtonWrapper,
  Modal,
  ModalHeader,
  NumberFormat
} from "../../../../ui";
import Lang from "../../../../components/Lang/Lang";

import { ReactComponent as AloneIcon } from "src/asset/illustrations/alone.svg";
import {
  walletBalanceSelector,
  walletSwapSelector
} from "../../../../selectors";
import { openModal } from "../../../../actions";

export default ({ onClose }) => {
  const swap = useSelector(walletSwapSelector);
  const balance = useSelector(walletBalanceSelector(swap.fromCurrency));
  // const router = useRouter();

  const needAmount = swap.fromAmount - (balance?.amount || 0);

  const useRefill = useCallback(() => {
    openModal("merchant", {
      amount: needAmount,
      currency: swap.fromCurrency
    });
  }, [needAmount, swap]);

  return (
    <Modal className="SwapInsufficientFunds" onClose={onClose}>
      <ModalHeader>
        <Lang name="cabinet_insufficientFundsModal_title" />
      </ModalHeader>
      <div className="SwapInsufficientFunds__content">
        <div className="SwapInsufficientFunds__icon">
          <AloneIcon />
        </div>
        <p>
          <Lang
            name="cabinet_insufficientFundsModal_text"
            params={{
              needAmount: (
                <NumberFormat
                  number={needAmount}
                  currency={swap.fromCurrency}
                />
              ),
              totalAmount: (
                <NumberFormat
                  number={swap.toAmount}
                  currency={swap.toCurrency}
                />
              )
            }}
          />
        </p>
        <ButtonWrapper align="center">
          <Button onClick={useRefill}>
            <Lang
              name="cabinet_insufficientFundsModal_actionButton"
              params={{
                currency: swap.fromCurrency.toUpperCase()
              }}
            />
          </Button>
        </ButtonWrapper>
      </div>
    </Modal>
  );
};
