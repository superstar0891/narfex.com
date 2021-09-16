import React from "react";
import Select, {
  customStyles
} from "../../../../../ui/components/Select/Select";

export default props => {
  return (
    <Select
      {...props}
      isSearchable={false}
      className="Swap__select"
      styles={{
        control: (provided, state) => ({
          ...customStyles.control(provided, state),
          boxShadow: null,
          // border: `1px solid ${getCssVar("--cloudy")}`,
          borderRadius: 16,
          minHeight: 64,
          ":hover": null
        }),
        option: (provided, state) => ({
          ...customStyles.option(provided, state),
          // border: '4px solid #f0f',
          margin: "4px",
          width: "calc(100% - 8px)",
          borderRadius: "14px"
          // fontWeight: 600,
          // padding: "11px 16px",
          // fontSize: '22px',
          // lineHeight: '32px',
        }),
        menu: (provided, state) => ({
          ...customStyles.menu(provided, state),
          borderRadius: "16px",
          padding: 0
        }),
        menuList: (provided, state) => ({
          padding: 0
        }),
        indicatorsContainer: provided => ({
          ...customStyles.indicatorsContainer(provided),
          marginRight: 16
        }),
        valueContainer: provided => ({
          ...customStyles.valueContainer(provided),
          padding: 16
        })
      }}
    />
  );
};
