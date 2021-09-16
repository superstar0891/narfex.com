import React, { useEffect } from "react";
import { connect } from "react-redux";
import CheckBox from "../../../ui/components/CheckBox/CheckBox";
import { valueChange } from "../../../actions/admin/";

const CheckBoxWrapper = props => {
  useEffect(() => valueChange(props.id, !!props.value), [
    props.id,
    props.value
  ]);
  const value = props.values[props.id];

  return (
    <CheckBox
      onChange={value => {
        valueChange(props.id, value);
      }}
      checked={value}
    >
      {props.title}
    </CheckBox>
  );
};

export default connect(state => ({
  values: state.admin.values
}))(CheckBoxWrapper);
