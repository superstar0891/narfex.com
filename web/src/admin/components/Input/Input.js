import React, { useEffect } from "react";
import { connect } from "react-redux";
import Input from "../../../ui/components/Input/Input";
import { valueChange } from "../../../actions/admin/";
const InputWrapper = props => {
  useEffect(() => {
    valueChange(props.id, props.value);
  }, [props.id, props.value]);

  const value = props.values[props.id];

  return (
    <div className="Item Item--input">
      {!!props.title && <div className="Item__title">{props.title}</div>}
      <Input
        {...props}
        onTextChange={value => {
          valueChange(props.id, value);
        }}
        value={value}
      />
    </div>
  );
};

export default connect(state => ({
  values: state.admin.values
}))(InputWrapper);
