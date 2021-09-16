import "./StaticContentModal.less";
//
import React, { useState, useEffect } from "react";
//
import * as UI from "src/ui";
import { getStaticPageContent } from "src/actions";
import ModalState from "../../cabinet/ModalState/ModalState";
import { Editor } from "src/ui";

export default props => {
  const { type } = props;
  const [status, setStatus] = useState("loading");
  const [data, setData] = useState({});

  const __load = () => {
    setStatus("loading");
    getStaticPageContent(type)
      .then(data => {
        setStatus(null);
        setData(data);
      })
      .catch(() => {
        setStatus("failed");
      });
  };

  useEffect(__load, [type]);

  return status ? (
    <ModalState onClose={props.onClose} status={status} onRetry={__load} />
  ) : (
    <UI.Modal
      isOpen={true}
      className="StaticContentModal"
      onClose={props.onClose}
    >
      <UI.ModalHeader>{data.title}</UI.ModalHeader>
      <div className="StaticContentModal__content__wrapper">
        {data.content && <Editor readOnly content={data.content} />}
        {/*<UI.Button fontSize={15} onClick={props.onBack}>*/}
        {/*  {utils.getLang("site__goBack")}*/}
        {/*</UI.Button>*/}
      </div>
    </UI.Modal>
  );
};
