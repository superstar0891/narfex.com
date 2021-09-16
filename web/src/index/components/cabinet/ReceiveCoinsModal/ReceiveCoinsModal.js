import "./ReceiveCoinsModal.less";

import React, { useCallback } from "react";
import { useDispatch, useSelector } from "react-redux";
import { useRoute, useRouter } from "react-router5";
import { Message, Button, Modal, ModalHeader } from "../../../../ui";
import Lang from "../../../../components/Lang/Lang";
import { currencySelector, walletBalanceSelector } from "../../../../selectors";
import QRCode from "qrcode.react";
import LoadingStatus from "../LoadingStatus/LoadingStatus";
import Clipboard from "../Clipboard/Clipboard";
import { walletSwapSetCurrency } from "../../../../actions/cabinet/wallet";
import * as pages from "../../../constants/pages";

export default ({ onClose }) => {
  const dispatch = useDispatch();
  const {
    route: { params }
  } = useRoute();
  const router = useRouter();
  const currency = useSelector(currencySelector(params.currency));
  const wallet = useSelector(walletBalanceSelector(params.currency));

  const handleBuy = useCallback(() => {
    dispatch(walletSwapSetCurrency("to", currency.abbr));
    router.navigate(pages.WALLET_SWAP);
    onClose();
  }, [router, dispatch, currency, onClose]);

  return (
    <Modal className="ReceiveCoinsModal" onClose={onClose}>
      <ModalHeader>
        <Lang name="cabinet_receiveCoinsModal_name" /> {currency.name}
      </ModalHeader>
      {wallet ? (
        <>
          <div className="ReceiveCoinsModal__layout">
            <div className="ReceiveCoinsModal__qrCode">
              <QRCode value={wallet.address} size={192 || 168} />
            </div>
            <div className="ReceiveCoinsModal__content">
              <Clipboard
                className="ReceiveCoinsModal__clipboard"
                text={wallet.address}
              />
              <Message type="warning" title={<Lang name="global_attention" />}>
                <Lang
                  name="cabinet_receiveCoinModal_attentionText"
                  params={{
                    currency: currency.name
                  }}
                />
              </Message>
            </div>
          </div>
          {/*{currency.can_exchange && (*/}
          {/*  <div className="ReceiveCoinsModal__banner">*/}
          {/*    <div className="ReceiveCoinsModal__banner__icon" />*/}
          {/*    <div className="ReceiveCoinsModal__banner__text">*/}
          {/*      <h4>*/}
          {/*        <Lang*/}
          {/*          name="cabinet_receiveCoinModal_banner_title"*/}
          {/*          params={{*/}
          {/*            currency: currency.name*/}
          {/*          }}*/}
          {/*        />*/}
          {/*      </h4>*/}
          {/*      <p>*/}
          {/*        <Lang*/}
          {/*          name="cabinet_receiveCoinModal_banner_text"*/}
          {/*          params={{*/}
          {/*            currency: currency.name*/}
          {/*          }}*/}
          {/*        />*/}
          {/*      </p>*/}
          {/*    </div>*/}
          {/*    <Button onClick={handleBuy}>*/}
          {/*      <Lang name="global_buy" />*/}
          {/*    </Button>*/}
          {/*  </div>*/}
          {/*)}*/}
        </>
      ) : (
        <LoadingStatus inline status="loading" />
      )}
    </Modal>
  );
};
