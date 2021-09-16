import "./Wisiwyg.less";
import React, { useEffect } from "react";
import { connect } from "react-redux";

import Editor from "../../../ui/components/Editor/Editor";
import { valueChange } from "../../../actions/admin/";
const WysiwygWrapper = props => {
  useEffect(() => valueChange(props.id, props.value), [props.id, props.value]);
  let value = props.value;

  if (typeof value === "string") {
    try {
      value = JSON.parse(value);
    } catch (e) {}
  }

  return (
    <div className="Item Item--input">
      {!!props.title && <div className="Item__title">{props.title}</div>}
      <Editor
        className="Wysiwyg"
        onChange={value => valueChange(props.id, value)}
        border
        on
        content={value}
      />
    </div>
  );
};

export default connect(state => ({
  values: state.admin.values
}))(WysiwygWrapper);
