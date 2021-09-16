import "./SwapConfirmModal.less";

import React, { useCallback } from "react";
import { useSelector, useDispatch } from "react-redux";
import {
  Button,
  ButtonWrapper,
  Modal,
  ModalHeader,
  NumberFormat
} from "../../../../ui";
import Lang from "../../../../components/Lang/Lang";
import SwapIndicator from "./components/SwapIndicator/SwapIndicator";
import {
  walletStatusSelector,
  walletSwapSelector
} from "../../../../selectors";
import { walletSwapSubmit } from "../../../../actions/cabinet/wallet";
import { isFiat } from "../../../../utils";

export default ({ onClose }) => {
  const swap = useSelector(walletSwapSelector);
  const dispatch = useDispatch(walletSwapSelector);
  const status = useSelector(walletStatusSelector("swap"));

  const handleSwap = useCallback(() => {
    dispatch(walletSwapSubmit());
  }, [dispatch]);

  const realRate = isFiat(swap.toCurrency) ? swap.rate : 1 / swap.rate;

  return (
    <Modal onClose={onClose} className="SwapConfirmModal">
      <ModalHeader>
        <Lang name="cabinet_fiatMarketExchangeTitle" />
      </ModalHeader>
      <div className="SwapConfirmModal__content">
        <SwapIndicator from={swap.fromCurrency} to={swap.toCurrency} />
        <div className="SwapConfirmModal__row">
          <div className="SwapConfirmModal__column">
            <div className="SwapConfirmModal__label">
              <Lang name="cabinet_fiatWalletGive" />
            </div>
            <div className="SwapConfirmModal__amount">
              <NumberFormat
                number={swap.fromAmount}
                currency={swap.fromCurrency}
              />
            </div>
            <div className="SwapConfirmModal__rate">
              <NumberFormat number={1} currency={swap.fromCurrency} />
              {" ≈ "}
              <NumberFormat
                skipRoughly
                number={realRate}
                currency={swap.toCurrency}
              />
            </div>
          </div>
          <div className="SwapConfirmModal__column">
            <div className="SwapConfirmModal__label">
              <Lang name="cabinet_fiatWalletGet" />
            </div>
            <div className="SwapConfirmModal__amount">
              <NumberFormat
                color
                symbol
                number={swap.toAmount}
                currency={swap.toCurrency}
              />
            </div>
            <div className="SwapConfirmModal__rate">
              <NumberFormat number={1} currency={swap.toCurrency} />
              {" ≈ "}
              <NumberFormat
                skipRoughly
                number={1 / realRate}
                currency={swap.fromCurrency}
              />
            </div>
          </div>
        </div>
        <ButtonWrapper align="center">
          <Button state={status} onClick={handleSwap}>
            <Lang name={"global_buy"} />{" "}
            <NumberFormat number={swap.toAmount} currency={swap.toCurrency} />
          </Button>
        </ButtonWrapper>
      </div>
    </Modal>
  );
};
