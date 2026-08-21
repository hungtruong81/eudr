"use client";
import { useQuery } from "@tanstack/react-query";
import { Modal, Spin, Table, Tag, Typography } from "antd";
import { getTransportationRouteByCode } from "@/features/route/transportation-route/actions";
import { ITransactionTicket } from "@/features/route/transportation-route/types";
import dayjs from "dayjs";
import { useTranslations } from "next-intl";
import AppModal from "@/components/modal";

const { Title, Text } = Typography;

interface ReceiveMaterialDetailProps {
  open: boolean;
  onClose: () => void;
  transportationRouteCode: string;
}

const mapColor: Record<string, string> = {
  pending: "processing",
  unloaded: "warning",
  arrived: "success",
  cancelled: "error",
};

const ReceiveMaterialDetail = ({
  open,
  onClose,
  transportationRouteCode,
}: ReceiveMaterialDetailProps) => {
  const t = useTranslations("Factory.receive_material");
  const tc = useTranslations("Common");
  const tf = useTranslations("Factory.fg_receipt");

  const mapStatus: Record<string, string> = {
    pending: t("pending"),
    unloaded: t("unloaded"),
    arrived: t("arrived"),
    cancelled: t("cancelled"),
  };

  const { data, isLoading } = useQuery({
    queryKey: ["transportation-route-detail", transportationRouteCode],
    queryFn: () => getTransportationRouteByCode(transportationRouteCode),
    enabled: !!transportationRouteCode && open,
  });

  const detail = data?.data;

  const detailColumns = [
    {
      title: t("route_info"),
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

  const ticketColumns = [
    {
      title: tf("code"),
      dataIndex: "transaction_ticket_code",
      key: "transaction_ticket_code",
      render: (val: string) => val?.toUpperCase(),
    },
    {
      title: t("seller"),
      dataIndex: "seller_name",
      key: "seller_name",
    },
    {
      title: tc("address"),
      dataIndex: "seller_address",
      key: "seller_address",
    },
    {
      title: tc("weight_kg"),
      key: "weight",
      render: (_: any, record: ITransactionTicket) => {
        if (record.transaction_ticket_type === "latex") {
          return `${record.latex_weight} kg (TSC: ${record.latex_tsc_grade}%)`;
        }
        return `${record.scrap_rubber_weight} kg (DRC: ${record.scrap_rubber_drc_grade}%)`;
      },
    },
  ];

  return (
    <AppModal
      title={`${t("route_info")}: ${transportationRouteCode?.toUpperCase()}`}
      open={open}
      onCancel={onClose}
      footer={null}
      width={1000}>
      <Spin spinning={isLoading}>
        {detail && (
          <div
            style={{ display: "flex", flexDirection: "column", gap: "24px" }}>
            <Table
              dataSource={[
                {
                  label: t("route_code"),
                  value: detail.transportation_route_code?.toUpperCase(),
                },
                {
                  label: t("status"),
                  value: (
                    <Tag color={mapColor[detail.status]}>
                      {mapStatus[detail.status]}
                    </Tag>
                  ),
                },
                {
                  label: tc("vehicle"),
                  value: `${detail.vehicle_name} (${detail.vehicle_license_plate})`,
                },
                { label: t("driver"), value: detail.driver_name },
                {
                  label: t("transport_date"),
                  value: detail.transport_date
                    ? dayjs(detail.transport_date).format("DD/MM/YYYY")
                    : "-",
                },
                { label: t("pickup_time"), value: detail.pickup_time },
                {
                  label: t("destination_factory"),
                  value: detail.destination_factory_name,
                },
                {
                  label: tf("created_date"),
                  value: detail.created_at
                    ? dayjs(detail.created_at).format("DD/MM/YYYY HH:mm")
                    : "-",
                },
              ]}
              columns={detailColumns}
              pagination={false}
              size="small"
              bordered
              rowKey="label"
            />

            <div>
              <Title level={5}>{t("purchase_tickets_list")}</Title>
              <Table
                dataSource={detail.source_transaction_tickets}
                columns={ticketColumns}
                rowKey="transaction_ticket_id"
                pagination={false}
                size="small"
              />
            </div>
          </div>
        )}
      </Spin>
    </AppModal>
  );
};

export default ReceiveMaterialDetail;
