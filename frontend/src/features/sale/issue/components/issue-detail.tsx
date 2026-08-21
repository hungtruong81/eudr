import { Drawer, Space, Spin, Table, Tag, Typography } from "antd";
import { useQuery } from "@tanstack/react-query";
import { getIssueByCode } from "../actions";
import { IIssueAllocation, IIssueItem } from "../types";
import dayjs from "dayjs";
import React from "react";
import { formatVnCurrency } from "@/lib/utils";
import { useTranslations } from "next-intl";
const { Text } = Typography;
interface IssueDetailProps {
  code: string | null;
  onClose: () => void;
}

const IssueDetail: React.FC<IssueDetailProps> = ({ code, onClose }) => {
  const t = useTranslations("Issue");
  const tc = useTranslations("Common");
  const ts = useTranslations("Status");
  const detailColumns = [
    {
      title: tc("info"),
      dataIndex: "label",
      key: "label",
      width: "40%",
      render: (text: string) => <Text type="secondary">{text}</Text>,
    },
    {
      title: tc("detail"),
      dataIndex: "value",
      key: "value",
      render: (text: any) => <Text strong>{text ?? "—"}</Text>,
    },
  ];
  const { data, isLoading } = useQuery({
    queryKey: ["issue-detail", code],
    queryFn: () => getIssueByCode(code!),
    enabled: !!code,
  });

  const issue = data?.issue;

  const allocationColumns = [
    {
      title: t("product_tank"),
      render: (_: any, record: IIssueAllocation) => (
        <span>
          {record.product_tank_name || record.raw_material_tank_name || ""}{" "}
        </span>
      ),
    },
    {
      title: t("purchase_ticket"),
      render: (_: any, record: IIssueAllocation) => (
        <div>
          {record.transaction_ticket_code ? (
            <Tag color="geekblue">{record.transaction_ticket_code}</Tag>
          ) : (
            "-"
          )}
          {record.ticket_seller_name && (
            <span className="block text-xs text-gray-500">
              {t("source")}: {record.ticket_seller_name}
            </span>
          )}
        </div>
      ),
    },
    {
      title: t("weight"),
      dataIndex: "weight_issued",
      render: (val: any) =>
        val ? (
          <span className="text-gray-600 font-semibold">{val} kg</span>
        ) : (
          "-"
        ),
    },
    {
      title: t("qty_issued"),
      dataIndex: "qty_issued",
      render: (val: any) => (
        <span className="text-green-600 font-semibold">{val} kg</span>
      ),
    },
  ];

  return (
    <Drawer
      title={
        <span className="uppercase">
          {t("detail_title")}: {code || ""}
        </span>
      }
      size={800}
      open={!!code}
      onClose={onClose}>
      {isLoading ? (
        <div className="flex justify-center p-10 mt-10">
          <Spin size="large" />
        </div>
      ) : issue ? (
        <Space orientation="vertical" size="large" className="w-full">
          <Table
            dataSource={[
              {
                label: t("issue_code"),
                value: issue.issue_code?.toUpperCase(),
              },
              {
                label: tc("status"),
                value: (
                  <Tag
                    color={
                      issue.status === "issued"
                        ? "green"
                        : issue.status === "draft"
                          ? "blue"
                          : "red"
                    }>
                    {ts(issue.status)}
                  </Tag>
                ),
              },
              {
                label: t("sale_order"),
                value: (
                  <>
                    #{issue.sale_order_id}{" "}
                    {issue.sale_order_code && `(${issue.sale_order_code})`}
                  </>
                ),
              },
              {
                label: t("issue_date"),
                value: dayjs(issue.issue_date).format("DD/MM/YYYY"),
              },
              { label: t("document_ref"), value: issue.document_ref },
              { label: t("receiver"), value: issue.receiver },
              { label: t("vehicle_no"), value: issue.vehicle_no },
              { label: t("shipper"), value: issue.shipper },
              { label: tc("notes"), value: issue.notes },
            ]}
            columns={detailColumns}
            pagination={false}
            size="small"
            bordered
            rowKey="label"
            showHeader={false}
          />

          <div>
            <h3 className="text-lg font-semibold mb-3">{t("items_title")}</h3>
            {issue.items?.length > 0 ? (
              <Space orientation="vertical" size="middle" className="w-full">
                {issue.items.map((item: IIssueItem, index: number) => (
                  <div
                    key={item.issue_item_id || index}
                    className="border border-gray-200 p-4 rounded-lg bg-gray-50/50">
                    <Table
                      dataSource={[
                        {
                          label: t("product_type_id"),
                          value: <Tag color="cyan">#{item.product_id}</Tag>,
                        },
                        {
                          label: t("qty_issued"),
                          value: (
                            <span className="text-blue-600 font-semibold">
                              {item.qty_issued} {item.uom}
                            </span>
                          ),
                        },
                        {
                          label: t("unit_price"),
                          value: item.price
                            ? formatVnCurrency(item.price)
                            : "-",
                        },
                        {
                          label: tc("notes"),
                          value: item.notes,
                        },
                      ].filter((row) => row.value)}
                      columns={detailColumns}
                      pagination={false}
                      size="small"
                      bordered
                      rowKey="label"
                      showHeader={false}
                      className="mb-4"
                    />

                    <h5 className="font-medium mb-2 text-gray-700">
                      {t("allocation_title")}:
                    </h5>
                    <Table
                      columns={allocationColumns}
                      dataSource={item.allocations || []}
                      rowKey={(a) => a.issue_allocation_id || Math.random()}
                      pagination={false}
                      size="small"
                      bordered
                    />
                  </div>
                ))}
              </Space>
            ) : (
              <div className="text-gray-500 italic text-center py-4">
                {tc("no_data")}
              </div>
            )}
          </div>
        </Space>
      ) : (
        <div className="text-center text-red-500 py-10">
          {t("not_found_error")}
        </div>
      )}
    </Drawer>
  );
};

export default IssueDetail;
