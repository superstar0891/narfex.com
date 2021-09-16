import React, { useEffect } from "react";
import { connect } from "react-redux";
import Select from "../../../ui/components/Select/Select";
import { valueChange } from "../../../actions/admin/";
const InputWrapper = props => {
  useEffect(() => valueChange(props.id, props.value), [props.id, props.value]);
  const value = props.values[props.id] || (props.multiple ? [] : "");

  const selectValue = props.multiple
    ? value
        .map(v => {
          return (
            Object.values(props.options).find(v2 => v2.value === v) || false
          );
        })
        .filter(Boolean)
    : Object.values(props.options).find(v => v.value === value);

  return (
    <div className="Item Item--select">
      {!!props.title && <div className="Item__title">{props.title}</div>}
      <Select
        {...props}
        options={Object.values(props.options)}
        isMulti={props.multiple}
        onChange={value => {
          if (props.multiple) {
            valueChange(props.id, value ? value.map(v => v.value) : []);
          } else {
            valueChange(props.id, value ? value.value : null);
          }
        }}
        value={selectValue}
      />
    </div>
  );
};

export default connect(state => ({
  values: state.admin.values
}))(InputWrapper);
