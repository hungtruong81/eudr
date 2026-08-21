import { EditOutlined } from "@ant-design/icons";
import { Space } from "antd";
import React from "react";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { TooltipButton } from "@/components/tooltip-button";
import { ICustomFieldEntitySummary } from "../types";
import { CRUD } from "@/types/permission-context";
import { useTranslations } from "next-intl";

interface CustomFieldTableProps {
  data: ICustomFieldEntitySummary[] | undefined;
  loading?: boolean;
  onEdit: (entityType: string) => void;
  permissions: CRUD;
}

const CustomFieldTable = ({
  data,
  loading,
  onEdit,
  permissions,
}: CustomFieldTableProps) => {
  const t = useTranslations("Manage.CustomField");
  const tc = useTranslations("Common");

  const columns: CustomColumnTypeTable<ICustomFieldEntitySummary>[] = [
    {
      title: t("entity_type"),
      dataIndex: "entity_type",
      render: (val: string) => {
        // Translation keys match "entity_" + val
        const key = `entity_${val}` as any;
        return <span className="font-medium">{t(key)}</span>;
      },
    },
    {
      title: t("total_fields"),
      dataIndex: "field_count",
      align: "center",
      render: (val: number) => <span className="font-bold">{val}</span>,
    },
    {
      title: tc("actions"),
      dataIndex: "actions",
      fixed: "right",
      align: "center",
      width: 150,
      render: (_, record) => (
        <Space>
          {(permissions.full || permissions.update) && (
            <TooltipButton
              tooltip={t("edit_form")}
              type="primary"
              icon={<EditOutlined />}
              onClick={() => onEdit(record.entity_type)}>
              {t("edit_form")}
            </TooltipButton>
          )}
        </Space>
      ),
    },
  ];

  return (
    <CustomTable<ICustomFieldEntitySummary>
      rowKey="entity_type"
      columns={columns}
      dataSource={data}
      loading={loading}
      tableId="custom-field-entity-table"
      pagination={false}
      scroll={{ x: 800 }}
    />
  );
};

export default CustomFieldTable;
