import "../FiatRefillModal/FiatRefillModal.less";

import React, { useEffect, useState, useCallback } from "react";
import { useDispatch, useSelector } from "react-redux";
import * as firebase from "firebase";

import Modal, { ModalHeader } from "../../../../ui/components/Modal/Modal";
import NumberFormat from "../../../../ui/components/NumberFormat/NumberFormat";
import BankList from "../FiatRefillModal/components/BankList/BankList";
import LoadingStatus from "../LoadingStatus/LoadingStatus";
import BankLogo from "../../../../ui/components/BankLogo/BankLogo";
import Clipboard from "src/index/components/cabinet/Clipboard/Clipboard";
import Button, { ButtonWrapper } from "../../../../ui/components/Button/Button";
import { getLang } from "../../../../utils";
import * as actionTypes from "../../../../actions/actionTypes";
import {
  walletCardReservationSelector,
  walletSelector,
  walletStatusesSelector
} from "src/selectors";
import { useAdaptive } from "src/hooks";
import { Message, Timer } from "../../../../ui";
import Lang from "../../../../components/Lang/Lang";
import { confirm, closeModal } from "src/actions/index";
import * as api from "../../../../services/api";
import apiSchema from "../../../../services/apiSchema";
import * as utils from "../../../../utils";
import * as toast from "../../../../actions/toasts";
import * as actions from "../../../../actions";

const CustomLoadingStatus = ({ status }) => {
  const props = {};
  if (status === "wait_for_review") {
    props.icon = require("src/asset/120/clock.svg");
    props.description = getLang(
      "fiatRefillCard_status_description_wait_for_review"
    );
    props.status = getLang("fiatRefillCard_status_wait_for_review");
  }
  return <LoadingStatus status={status} {...props} />;
};

export default props => {
  const dispatch = useDispatch();
  const { refillBankList } = useSelector(walletSelector);
  const status = useSelector(walletStatusesSelector);
  const cardReservation = useSelector(walletCardReservationSelector);
  const adaptive = useAdaptive();
  const [timeIsOver, setTimeIsOver] = useState(false);

  const { minFee, percentFee, currency } = props;

  const amount = cardReservation
    ? cardReservation.reservation.amount
    : props.amount;

  const fee = cardReservation
    ? cardReservation.reservation.fee
    : Math.max((amount / 100) * percentFee, minFee);

  const handleTimerFinish = useCallback(() => {
    setTimeIsOver(true);
  }, [setTimeIsOver]);

  useEffect(() => {
    if (!cardReservation && !props.amount) {
      props.onClose();
    }

    firebase.analytics().logEvent("open_rub_fiat_refill_modal");

    if (
      cardReservation?.card?.expire_in * 1000 <= Date.now() &&
      cardReservation.reservation.status === "wait_for_pay"
    ) {
      // time is over hack
      dispatch({
        type: actionTypes.FIAT_SET_RESERVED_CARD,
        payload: null
      });
      actions.closeModal();
      actions.openModal("merchant", {
        currency: "rub"
      });
    }

    if (!cardReservation) {
      dispatch({
        type: actionTypes.WALLET_SET_STATUS,
        section: "refillBankList",
        status: "loading"
      });

      api
        .call(apiSchema.Fiat_wallet.Cards.RefillBanksGet, {
          amount: props.amount
        })
        .then(r => {
          if (r.status === "already_booked") {
            dispatch({
              type: actionTypes.WALLET_SET_CARD_RESERVATION,
              payload: r
            });
          } else {
            dispatch({
              type: actionTypes.WALLET_SET_REFILL_BANK_LIST,
              banks: r
            });
          }
        })
        .catch(err => {
          toast.error(err.message);
        })
        .finally(() => {
          dispatch({
            type: actionTypes.WALLET_SET_STATUS,
            section: "refillBankList",
            status: ""
          });
        });
    }
    return () => {
      // dispatch({
      //   type: actionTypes.FIAT_WALLETS_CLEAR_LOADING_STATUSES
      // });
    };
  }, [dispatch, props, cardReservation]);

  const handleChoiceBank = bankCode => {
    dispatch({
      type: actionTypes.WALLET_SET_STATUS,
      section: "reservedCard",
      status: "loading"
    });

    api
      .call(apiSchema.Fiat_wallet.Cards.ReservationPost, {
        amount,
        bank_code: bankCode
      })
      .then(response => {
        dispatch({
          type: actionTypes.WALLET_SET_CARD_RESERVATION,
          payload: response
        });
        dispatch({
          type: actionTypes.WALLET_SET_STATUS,
          section: "reservedCard",
          status: ""
        });
      })
      .catch(err => {
        dispatch({
          type: actionTypes.WALLET_SET_STATUS,
          section: "reservedCard",
          status: err.status
        });
      });
  };

  const handleCancel = () => {
    dispatch({
      type: actionTypes.WALLET_SET_STATUS,
      section: "cancelReservation",
      status: "loading"
    });

    confirm({
      title: <Lang name="fiatRefillCard_cancelReservation_confirmTitle" />,
      content: <Lang name="fiatRefillCard_cancelReservation_confirmText" />,
      okText: <Lang name="fiatRefillCard_cancelReservation_confirmOk" />,
      cancelText: (
        <Lang name="fiatRefillCard_cancelReservation_confirmCancel" />
      ),
      type: "negative",
      dontClose: true
    }).then(() => {
      api
        .call(apiSchema.Fiat_wallet.Cards.ReservationDelete, {
          amount,
          reservation_id: cardReservation.reservation.id
        })
        .then(() => {
          dispatch({
            type: actionTypes.WALLET_SET_CARD_RESERVATION,
            payload: null
          });
          dispatch({
            type: actionTypes.WALLET_SET_STATUS,
            section: "cancelReservation",
            status: ""
          });
        })
        .finally(() => {
          closeModal();
        });
    });
  };

  const handleClickBack = () => {
    dispatch({
      type: actionTypes.WALLET_SET_CARD_RESERVATION,
      payload: null
    });

    dispatch({
      type: actionTypes.WALLET_SET_STATUS,
      section: "reservedCard",
      status: ""
    });
  };

  const handleConfirmPayment = () => {
    dispatch({
      type: actionTypes.WALLET_SET_STATUS,
      section: "confirmPayment",
      status: "loading"
    });
    api
      .call(apiSchema.Fiat_wallet.Cards["Reservation/confirmPaymentPost"], {
        reservation_id: cardReservation.reservation.id
      })
      .then(({ status }) => {
        dispatch({
          type: actionTypes.WALLET_SET_CARD_RESERVATION,
          payload: {
            ...cardReservation,
            reservation: {
              ...cardReservation.reservation,
              status
            }
          }
        });
      })
      .catch(err => {
        toast.error(err.message);
      })
      .finally(() => {
        dispatch({
          type: actionTypes.WALLET_SET_STATUS,
          section: "confirmPayment",
          status: ""
        });
      });
  };

  const renderBody = () => {
    if (timeIsOver) {
      return (
        <>
          <div>
            <LoadingStatus
              icon={require("src/asset/120/error.svg")}
              status={<Lang name="fiatRefillCard_timeIsOver_title" />}
              description={
                <Lang name="fiatRefillCard_timeIsOver_description" />
              }
            />
          </div>
          <ButtonWrapper
            align="center"
            className="FiatRefillModal__body__footer"
          >
            <Button onClick={props.onClose}>{getLang("global_close")}</Button>
          </ButtonWrapper>
        </>
      );
    }

    if (status.reservedCard === "not_available_cards") {
      return (
        <>
          <div style={{ flex: 1, display: "flex" }}>
            <LoadingStatus
              inline
              icon={require("src/asset/120/info.svg")}
              status={<Lang name="fiatRefillCard_status_not_available_cards" />}
              description={
                <Lang name="fiatRefillCard_status_description_not_available_cards" />
              }
            />
          </div>
          <ButtonWrapper
            align="center"
            className="FiatRefillModal__body__footer"
          >
            <Button onClick={handleClickBack}>{getLang("global_back")}</Button>
          </ButtonWrapper>
        </>
      );
    }

    if ([status.refillBankList, status.reservedCard].some(Boolean)) {
      return (
        <CustomLoadingStatus
          status={status.refillBankList || status.reservedCard}
        />
      );
    }

    if (cardReservation?.reservation.status === "wait_for_review") {
      return (
        <>
          <div style={{ margin: "auto" }}>
            <LoadingStatus
              inline
              icon={require("src/asset/120/clock.svg")}
              status={getLang("fiatRefillCard_status_wait_for_review")}
              description={getLang(
                "fiatRefillCard_status_description_wait_for_review"
              )}
            />
          </div>
          <ButtonWrapper
            align="justify"
            className="FiatRefillModal__body__footer"
          >
            <Button onClick={handleCancel} type="secondary">
              {getLang("global_cancel")}
            </Button>
            <Button onClick={props.onClose}>{getLang("global_ok")}</Button>
          </ButtonWrapper>
        </>
      );
    }

    if (!cardReservation) {
      return (
        <>
          <div className="FiatRefillModal__header">
            {getLang("cabinet_fiatWithdrawalModal_chooseBank")}
          </div>
          <BankList
            onChange={b => handleChoiceBank(b.code)}
            items={refillBankList}
          />
          <ButtonWrapper
            align="right"
            className="FiatRefillModal__body__footer"
          >
            <Button onClick={props.onBack} type="secondary">
              {getLang("global_back")}
            </Button>
          </ButtonWrapper>
        </>
      );
    } else {
      return (
        <>
          <div className="FiatRefillModal__body__content">
            <div className="FiatRefillModal__header">
              {cardReservation.card.bank.name}
            </div>
            <p>
              <BankLogo name={cardReservation.card.bank.code} />
            </p>
            <Message title={<Lang name="global_attention" />} type="warning">
              <Lang name="fiatRefillCard_attention_text_sendExactly" />{" "}
              <strong>
                <NumberFormat number={amount} currency={currency} />{" "}
                <Lang name="fiatRefillCard_attention_text_oneTransaction" />
              </strong>{" "}
              <Lang name="fiatRefillCard_attention_text" />
            </Message>
            <div className="FiatRefillModal__infoBlock">
              <div className="FiatRefillModal__infoBlock__item">
                <span>
                  <Lang name="fiatRefillCard_cardReservation" />{" "}
                  {utils.dateFormat(cardReservation.card.expire_in)}
                </span>
                <strong>
                  <Timer
                    onFinish={handleTimerFinish}
                    time={cardReservation.card.expire_in * 1000}
                  />
                </strong>
              </div>
              <div className="FiatRefillModal__infoBlock__item">
                <span>
                  <Lang name="fiatRefillCard_paymentAmount" />
                </span>
                <strong>
                  <NumberFormat number={amount} currency={currency} />
                </strong>
              </div>
            </div>
            <div className="FiatRefillModal__infoBlock">
              <div className="FiatRefillModal__infoBlock__item primary">
                <span>
                  <Lang name="fiatRefillCard_cardNumberForRefill" />
                </span>
                <strong>
                  <Clipboard
                    displayText={cardReservation.card.number
                      .match(/.{1,4}/g)
                      .join(" ")}
                    text={cardReservation.card.number}
                  />
                </strong>
                <span>
                  <Lang name="fiatRefillCard_cardHolderName" />
                </span>
                <strong className="holderName">
                  {cardReservation.card.bank.holder_name}
                </strong>
              </div>
            </div>
          </div>

          <ButtonWrapper
            align="justify"
            className="FiatRefillModal__body__footer"
          >
            <Button
              stete={status.cancelReservation}
              onClick={handleCancel}
              type="secondary"
            >
              <Lang name="fiatRefillCard_cancelReservation" />
            </Button>
            <Button
              state={status.confirmPayment}
              onClick={handleConfirmPayment}
            >
              <Lang name="fiatRefillCard_confirmPayment" />
            </Button>
          </ButtonWrapper>
        </>
      );
    }
  };

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
              <small>
                <Lang name="global_amount" />
              </small>
              <strong>
                <NumberFormat number={amount} currency={currency} />
              </strong>
            </div>
            <div className="FiatRefillModal__sideBar__fee">
              <small>
                <Lang name="global_fee" />
              </small>
              <strong>
                <NumberFormat number={fee} currency={currency} />
              </strong>
            </div>
            <hr />
            <div className="FiatRefillModal__sideBar__amount">
              <small>
                <Lang name="fiatRefillCard_totalAmount" />
              </small>
              <strong>
                <NumberFormat number={amount - fee} currency={currency} />
              </strong>
            </div>
          </div>
        </div>
        <div className="FiatRefillModal__body">{renderBody()}</div>
      </div>
    </Modal>
  );
};
