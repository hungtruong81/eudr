"use client";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { TooltipButton } from "@/components/tooltip-button";
import {
  getTransportationRoutes,
  IGetTransportationRouteParams,
} from "@/features/route/transportation-route/actions";
import { ITransportationRoute } from "@/features/route/transportation-route/types";
import { DatabaseOutlined, EyeOutlined } from "@ant-design/icons";
import { useQuery } from "@tanstack/react-query";
import { Form, Space, Tag } from "antd";
import { useState } from "react";
import dayjs from "dayjs";
import ReceiveMaterialFilter from "./receive-material-filter";
import ReceiveMaterialDetail from "./receive-material-detail";
import UnloadedReceiveMaterialTank from "./unloaded-receive-material-tank";
import { useTranslations } from "next-intl";

const mapColor: Record<string, string> = {
  pending: "processing",
  unloaded: "warning",
  arrived: "success",
  cancelled: "error",
};

const ReceiveMaterial = () => {
  const t = useTranslations("Factory.receive_material");
  const tc = useTranslations("Common");

  const mapStatus: Record<string, string> = {
    pending: t("pending"),
    unloaded: t("unloaded"),
    arrived: t("arrived"),
    cancelled: t("cancelled"),
  };

  const [params, setParams] = useState<IGetTransportationRouteParams>({
    page: 1,
    limit: 10,
    status: "arrived",
    start_date: dayjs().subtract(1, "month").format("YYYY-MM-DD"),
    end_date: dayjs().format("YYYY-MM-DD"),
  });
  const [filterForm] = Form.useForm();
  const [openDetail, setOpenDetail] = useState(false);
  const [selectedCode, setSelectedCode] = useState("");
  const [selectedRouteForUnload, setSelectedRouteForUnload] =
    useState<ITransportationRoute | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ["transportation-routes-receive", params],
    queryFn: () => getTransportationRoutes(params),
  });

  const handleFilter = (values: any) => {
    setParams({
      ...params,
      ...values,
      page: 1,
    });
  };

  const handleResetFilter = () => {
    filterForm.resetFields();
    setParams({
      page: 1,
      limit: 10,
      status: "all",
      start_date: dayjs().subtract(1, "month").format("YYYY-MM-DD"),
      end_date: dayjs().format("YYYY-MM-DD"),
    });
  };

  const columns: CustomColumnTypeTable<ITransportationRoute>[] = [
    {
      title: t("route_code"),
      dataIndex: "transportation_route_code",
      render: (val) => val?.toUpperCase(),
    },
    { title: t("vehicle"), dataIndex: "vehicle_name" },
    { title: t("driver"), dataIndex: "driver_name" },
    { title: t("transport_date"), dataIndex: "transport_date", type: "date" },
    { title: t("pickup_time"), dataIndex: "pickup_time" },
    { title: t("destination_factory"), dataIndex: "destination_factory_name" },
    {
      title: t("status"),
      dataIndex: "status",
      render: (val) => <Tag color={mapColor[val]}>{mapStatus[val]}</Tag>,
    },
    {
      title: tc("actions"),
      dataIndex: "actions",
      render: (_, record) => (
        <Space>
          <TooltipButton
            tooltip={t("unload_to_tank_tooltip")}
            type="primary"
            icon={<DatabaseOutlined />}
            onClick={() => setSelectedRouteForUnload(record)}
            disabled={record.status !== "arrived"}
          />

          <TooltipButton
            tooltip={t("view_route_detail_tooltip")}
            type="dashed"
            icon={<EyeOutlined />}
            onClick={() => {
              setSelectedCode(record.transportation_route_code);
              setOpenDetail(true);
            }}
          />
        </Space>
      ),
    },
  ];

  if (selectedRouteForUnload) {
    return (
      <UnloadedReceiveMaterialTank
        transportationRoute={selectedRouteForUnload}
        onBack={() => setSelectedRouteForUnload(null)}
      />
    );
  }

  return (
    <Space orientation="vertical" style={{ width: "100%" }}>
      <ReceiveMaterialFilter
        filterForm={filterForm}
        handleFilter={handleFilter}
        handleResetFilter={handleResetFilter}
      />
      <CustomTable<ITransportationRoute>
        rowKey="transportation_route_id"
        columns={columns}
        dataSource={data?.data?.records || []}
        loading={isLoading}
        tableId="transportation-route-table"
        pagination={{
          current: data?.data?.current_page || 1,
          pageSize: data?.data?.page_limit || 10,
          total: data?.data?.total_records || 0,
        }}
        onChange={(pagination) => {
          setParams({
            ...params,
            page: pagination.current || 1,
            limit: pagination.pageSize || 10,
          });
        }}
        scroll={{ x: "max-content" }}
      />

      <ReceiveMaterialDetail
        open={openDetail}
        onClose={() => setOpenDetail(false)}
        transportationRouteCode={selectedCode}
      />
    </Space>
  );
};

export default ReceiveMaterial;
