import "./SavingsModal.less";

import React, { useEffect, useCallback, useState } from "react";
import { Button, Editor, Modal, ModalHeader } from "src/ui";
import { useRoute } from "react-router5";
import Lang from "../../../../components/Lang/Lang";
import { getStaticPageContent } from "../../../../actions";
import ModalState from "../ModalState/ModalState";
import { useDispatch, useSelector } from "react-redux";
import { walletEnableSaving } from "../../../../actions/cabinet/wallet";
import {
  walletBalanceSelector,
  walletStatusSelector
} from "../../../../selectors";

export default ({ onClose }) => {
  const [status, setStatus] = useState("loading");
  const [data, setData] = useState({});
  const dispatch = useDispatch();
  const {
    route: { params }
  } = useRoute();
  const balance = useSelector(walletBalanceSelector(params.currency));
  const savingStatus = useSelector(walletStatusSelector("saving"));

  const handleLoad = useCallback(() => {
    setStatus("loading");
    getStaticPageContent("savings")
      .then(data => {
        setStatus(null);
        setData(data);
      })
      .catch(() => {
        setStatus("failed");
      });
  }, [setStatus, setData]);

  useEffect(() => {
    handleLoad();
  }, [handleLoad]);

  const handleEnable = useCallback(() => {
    dispatch(walletEnableSaving(balance.id));
  }, [dispatch, balance]);

  return status ? (
    <ModalState onClose={onClose} status={status} onRetry={handleLoad} />
  ) : (
    <Modal className="SavingsModal" onClose={onClose}>
      <ModalHeader>{data.title}</ModalHeader>

      {data.content && <Editor readOnly content={data.content} />}
      <div className="SavingsModal__buttonWrapper">
        <Button state={savingStatus} onClick={handleEnable}>
          <Lang name="cabinet_saving_actionButtonToPlug" />
        </Button>
      </div>
    </Modal>
  );
};
