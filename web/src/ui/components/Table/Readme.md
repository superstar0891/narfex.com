```js
import Table, { TableCell, TableColumn } from "./Table.js";

<Table
  headings={[
    <TableColumn>Title 1</TableColumn>,
    <TableColumn sub="sub">Title 2</TableColumn>,
    <TableColumn>Title 3</TableColumn>,
    <TableColumn>Title 4</TableColumn>
  ]}
>
  <TableCell>
    <TableColumn>Column 1</TableColumn>
    <TableColumn sub="sub">Column 2</TableColumn>
    <TableColumn>Column 3</TableColumn>
    <TableColumn>Column 4</TableColumn>
  </TableCell>
  <TableCell>
    <TableColumn>Column 1</TableColumn>
    <TableColumn sub="sub">Column 2</TableColumn>
    <TableColumn>Column 3</TableColumn>
    <TableColumn>Column 4</TableColumn>
  </TableCell>
  <TableCell>
    <TableColumn>Column 1</TableColumn>
    <TableColumn sub="sub">Column 2</TableColumn>
    <TableColumn>Column 3</TableColumn>
    <TableColumn>Column 4</TableColumn>
  </TableCell>
</Table>;
```
