import "./Table.less";

import React from "react";

import Table, {
  TableCell,
  TableColumn
} from "../../../ui/components/Table/Table";
import Item from "../Item/Item";
import EmptyContentBlock from "../../../index/components/cabinet/EmptyContentBlock/EmptyContentBlock";
import action from "../../../actions/admin";
import Button from "../../../ui/components/Button/Button";
import { valueChange } from "../../../actions/admin/";

export default class TableComponent extends React.Component {
  clearFields() {
    this.props.search &&
      this.props.search.fields.forEach(field => {
        valueChange(this.props.id + "_value_" + field.id, "");
      });
  }

  componentWillUpdate(nextProps) {
    if (nextProps.id !== this.props.id) {
      this.clearFields();
    }
  }

  componentWillUnmount() {
    this.clearFields();
  }

  renderSearch = () => {
    const getKey = fieldId => {
      return this.props.id + "_value_" + fieldId;
    };

    if (!this.props.search) return false;
    return (
      <form
        onSubmit={e => {
          e.preventDefault();
          const values = {};
          this.props.search.fields.forEach(field => {
            values[field.id] = getKey(field.id);
          });
          this.props.search.action &&
            action({
              ...this.props.search.action,
              values
            });
          return true;
        }}
        className="Table__search"
      >
        {this.props.search.fields.map(field => (
          <Item
            {...{ ...field, id: getKey(field.id) }}
            item={{ type: field.type || "input" }}
            value={
              (this.props.filters.find(f => f.name === field.id) || {}).value
            }
          />
        ))}
        <Button btnType="submit">Search</Button>
      </form>
    );
  };

  render() {
    const { props } = this;

    return (
      <div className="AdminTable">
        {this.renderSearch()}
        <div className="Table__controls">
          {props.filters && (
            <div className="Table__filters">
              {props.filters.map(item => (
                <Item item={item} />
              ))}
            </div>
          )}
          {props.paging && (
            <Item totalCount={props.total_count} item={props.paging} />
          )}
        </div>

        {props.items.length ? (
          <Table
            skipContentBox
            headings={props.header.items.map(column => (
              <TableColumn sub={column.sub_value}>
                <Item item={column.items} />
              </TableColumn>
            ))}
          >
            {props.items
              .filter(row => row.type !== "deleted")
              .map((row, key) => (
                <TableCell key={key} mode={row.style}>
                  {row.items.map((column, key) => (
                    <TableColumn key={key} sub={column.sub_value}>
                      <Item item={column.items} />
                    </TableColumn>
                  ))}
                </TableCell>
              ))}
          </Table>
        ) : (
          <EmptyContentBlock
            skipContentClass
            icon={require("../../../asset/120/info.svg")}
            message="Empty table"
          />
        )}
      </div>
    );
  }
}
