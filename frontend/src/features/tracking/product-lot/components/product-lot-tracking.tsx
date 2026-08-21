"use client";

import React, { Suspense, useCallback, useEffect, useMemo } from "react";
import {
  Input,
  Button,
  Card,
  Spin,
  Space,
  Typography,
  Empty,
  Tag,
  Table,
  Row,
  Col,
} from "antd";
import { useQuery } from "@tanstack/react-query";
import { useSearchParams, useRouter, usePathname } from "next/navigation";
import { GoogleMap, InfoWindow, Polygon } from "@react-google-maps/api";
import { getProductLotTracking, exportProductLot } from "../actions";
import { ITraceFarm } from "../types";
import { formatDateDDMMYYYY } from "@/lib/utils";
import { DownloadOutlined } from "@ant-design/icons";
import { message } from "antd";

const { Title, Text } = Typography;

const detailColumns = [
  {
    title: "Thông tin",
    dataIndex: "label",
    key: "label",
    width: "40%",
    render: (text: string) => <Text type="secondary">{text}</Text>,
  },
  {
    title: "Chi tiết",
    dataIndex: "value",
    key: "value",
    render: (text: any) => <Text strong>{text ?? "—"}</Text>,
  },
];

const MAP_CONTAINER_STYLE = {
  width: "100%",
  height: "500px",
};

const DEFAULT_CENTER = {
  lat: 16.047079,
  lng: 108.20623,
};

const POLYGON_COLORS = [
  { fill: "#1677ff", stroke: "#0958d9" },
  { fill: "#52c41a", stroke: "#389e0d" },
  { fill: "#fa8c16", stroke: "#d46b08" },
  { fill: "#eb2f96", stroke: "#c41d7e" },
  { fill: "#722ed1", stroke: "#531dab" },
  { fill: "#13c2c2", stroke: "#08979c" },
];

const parseCoordinates = (
  coordString: string,
): { lat: number; lng: number }[] => {
  try {
    return JSON.parse(coordString);
  } catch {
    return [];
  }
};

const getEudrTag = (status: number) =>
  status === 1 ? (
    <Tag color="green">EUDR</Tag>
  ) : (
    <Tag color="default">Non-EUDR</Tag>
  );

const ProductLotTrackingContent = () => {
  const searchParams = useSearchParams();
  const router = useRouter();
  const pathname = usePathname();
  const cParam = searchParams.get("c");

  const [lotCode, setLotCode] = React.useState(cParam || "");
  const [searchCode, setSearchCode] = React.useState(cParam || "");
  const [selectedFarm, setSelectedFarm] = React.useState<ITraceFarm | null>(
    null,
  );
  const [selectedPosition, setSelectedPosition] = React.useState<{
    lat: number;
    lng: number;
  } | null>(null);
  const [isExporting, setIsExporting] = React.useState(false);

  useEffect(() => {
    if (cParam && cParam !== searchCode) {
      setSearchCode(cParam);
      setLotCode(cParam);
    }
  }, [cParam, searchCode]);

  const {
    data: traceData,
    isLoading,
    isError,
  } = useQuery({
    queryKey: ["product-lot-tracking", searchCode],
    queryFn: () => getProductLotTracking(searchCode),
    enabled: !!searchCode,
    retry: false,
  });

  const handleSearch = () => {
    const trimmed = lotCode.trim();
    if (trimmed) {
      setSearchCode(trimmed);
      const params = new URLSearchParams(searchParams.toString());
      params.set("c", trimmed);
      router.push(`${pathname}?${params.toString()}`);
    }
  };

  const farms = useMemo(() => traceData?.farms ?? [], [traceData?.farms]);
  const productLot = traceData?.product_lot;

  const handlePolygonClick = (
    farm: ITraceFarm,
    event: google.maps.MapMouseEvent,
  ) => {
    if (event.latLng) {
      setSelectedPosition({ lat: event.latLng.lat(), lng: event.latLng.lng() });
      setSelectedFarm(farm);
    }
  };

  const onLoadMap = useCallback(
    (map: google.maps.Map) => {
      if (farms.length === 0) return;

      const bounds = new window.google.maps.LatLngBounds();
      let hasCoords = false;

      farms.forEach((farm) => {
        const coords = parseCoordinates(farm.coordinates);
        if (coords.length > 0) {
          hasCoords = true;
          coords.forEach((c) =>
            bounds.extend(new window.google.maps.LatLng(c.lat, c.lng)),
          );
        }
      });

      if (hasCoords) {
        map.fitBounds(bounds);
      }
    },
    [farms],
  );

  const handleExport = async () => {
    if (!searchCode) return;

    setIsExporting(true);
    try {
      const blob = await exportProductLot(searchCode);
      const url = window.URL.createObjectURL(new Blob([blob]));
      const link = document.createElement("a");
      link.href = url;
      link.setAttribute(
        "download",
        `ProductLot_${searchCode.toUpperCase()}_${new Date()
          .toISOString()
          .split("T")[0]
          .replace(/-/g, "")}.xlsx`,
      );
      document.body.appendChild(link);
      link.click();
      link.parentNode?.removeChild(link);
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error("Export error:", error);
      message.error("Có lỗi xảy ra khi xuất DDS.");
    } finally {
      setIsExporting(false);
    }
  };

  const farmColumns = [
    {
      title: "Tên lô đất",
      dataIndex: "plot_name",
      key: "plot_name",
      render: (text: string) => <Text strong>{text}</Text>,
    },
    {
      title: "Nông dân",
      dataIndex: "farmer_name",
      key: "farmer_name",
    },
    {
      title: "Địa chỉ",
      dataIndex: "address",
      key: "address",
    },
    {
      title: "Diện tích (ha)",
      dataIndex: "land_area",
      key: "land_area",
      align: "right" as const,
    },
    {
      title: "Phân loại",
      dataIndex: "classify",
      key: "classify",
    },
    {
      title: "EUDR",
      dataIndex: "eudr_status",
      key: "eudr_status",
      render: (status: number) => getEudrTag(status),
    },
    {
      title: "Sở hữu",
      dataIndex: "ownership",
      key: "ownership",
    },
  ];

  const productLotColumns = [
    {
      title: "Mã lô",
      dataIndex: "product_lot_code",
      key: "product_lot_code",
      render: (text: string) => <Text strong>{text?.toUpperCase()}</Text>,
    },
    {
      title: "Trạng thái",
      dataIndex: "status",
      key: "status",
      render: (status: string) => (
        <Tag color={status === "confirmed" ? "green" : "default"}>
          {status === "confirmed" ? "Đã xác nhận" : status}
        </Tag>
      ),
    },
    {
      title: "Phân loại",
      dataIndex: "grade",
      key: "grade",
    },
    {
      title: "Số bành",
      dataIndex: "total_blocks",
      key: "total_blocks",
    },
    {
      title: "Khối lượng",
      dataIndex: "total_weight",
      key: "total_weight",
      render: (val: string) => `${Number(val).toLocaleString()} kg`,
      align: "right" as const,
    },
    {
      title: "Tổng Vườn",
      dataIndex: "total_farms",
      key: "total_farms",
      render: (val: number) => <Text strong>{val}</Text>,
      align: "right" as const,
    },
    {
      title: "Ngày SX từ",
      dataIndex: "production_date_from",
      key: "production_date_from",
      render: (date: string) => formatDateDDMMYYYY(date),
    },
    {
      title: "Ngày SX đến",
      dataIndex: "production_date_to",
      key: "production_date_to",
      render: (date: string) => formatDateDDMMYYYY(date),
    },
  ];

  return (
    <>
      <Card style={{ marginBottom: 24 }}>
        <Space orientation="vertical" style={{ width: "100%" }}>
          <div>
            <Title level={3} style={{ margin: 0 }}>
              Tra cứu Product Lot
            </Title>
            <Text type="secondary">
              Nhập mã lô sản phẩm để tra cứu truy xuất nguồn gốc và hiển thị vị
              trí đất canh tác trên bản đồ.
            </Text>
          </div>

          <Space.Compact style={{ width: "100%" }}>
            <Input
              placeholder="Nhập mã product lot (ví dụ: prdl-260407-xxxx)"
              value={lotCode}
              onChange={(e) => setLotCode(e.target.value)}
              onPressEnter={handleSearch}
              size="large"
            />
            <Button
              type="primary"
              size="large"
              onClick={handleSearch}
              loading={isLoading}>
              Tra cứu
            </Button>
          </Space.Compact>
        </Space>
      </Card>

      <Spin spinning={isLoading}>
        {traceData && productLot ? (
          <Space orientation="vertical" size="large" style={{ width: "100%" }}>
            {/* Google Maps */}
            <Card
              title={`Bản đồ Vườn (${farms.length} lô đất)`}
              extra={
                <Button
                  type="primary"
                  icon={<DownloadOutlined />}
                  onClick={handleExport}
                  loading={isExporting}
                  disabled={isExporting}>
                  Xuất DDS
                </Button>
              }>
              <div
                style={{
                  border: "1px solid #f0f0f0",
                  borderRadius: 8,
                  overflow: "hidden",
                }}>
                <GoogleMap
                  mapContainerStyle={MAP_CONTAINER_STYLE}
                  center={DEFAULT_CENTER}
                  zoom={6}
                  onLoad={onLoadMap}
                  options={{
                    mapTypeId: "satellite",
                  }}>
                  {farms.map((farm, index) => {
                    const coords = parseCoordinates(farm.coordinates);
                    if (coords.length < 3) return null;
                    const color = POLYGON_COLORS[index % POLYGON_COLORS.length];

                    return (
                      <Polygon
                        key={farm.plot_id}
                        paths={coords}
                        options={{
                          fillColor: color.fill,
                          fillOpacity: 0.4,
                          strokeColor: color.stroke,
                          strokeOpacity: 0.8,
                          strokeWeight: 2,
                        }}
                        onClick={(e) => handlePolygonClick(farm, e)}
                      />
                    );
                  })}

                  {selectedFarm && selectedPosition && (
                    <InfoWindow
                      position={selectedPosition}
                      onCloseClick={() => {
                        setSelectedFarm(null);
                        setSelectedPosition(null);
                      }}>
                      <div style={{ minWidth: 260, maxWidth: 320 }}>
                        <Table
                          dataSource={[
                            {
                              label: "Tên lô",
                              value: selectedFarm.plot_name?.toUpperCase(),
                            },
                            { label: "Nông hộ", value: selectedFarm.farmer_name },
                            { label: "Địa chỉ", value: selectedFarm.address },
                            {
                              label: "Diện tích",
                              value: `${selectedFarm.land_area} ha`,
                            },
                            { label: "Phân loại", value: selectedFarm.classify },
                            { label: "Sở hữu", value: selectedFarm.ownership },
                            {
                              label: "EUDR",
                              value: getEudrTag(selectedFarm.eudr_status),
                            },
                          ]}
                          columns={detailColumns}
                          pagination={false}
                          size="small"
                          bordered
                          rowKey="label"
                          showHeader={false}
                        />
                      </div>
                    </InfoWindow>
                  )}
                </GoogleMap>
              </div>

              {/* Map Legend */}
              <Row gutter={[8, 8]} style={{ marginTop: 12 }}>
                {farms.map((farm, index) => {
                  const color = POLYGON_COLORS[index % POLYGON_COLORS.length];
                  return (
                    <Col key={farm.plot_id}>
                      <Space size="small">
                        <div
                          style={{
                            width: 14,
                            height: 14,
                            borderRadius: 3,
                            backgroundColor: color.fill,
                            border: `2px solid ${color.stroke}`,
                          }}
                        />
                        <Text style={{ fontSize: 12 }}>
                          {farm.plot_name} ({farm.land_area} ha)
                        </Text>
                      </Space>
                    </Col>
                  );
                })}
              </Row>
            </Card>

            <Card title="Thông tin lô sản phẩm">
              <Table
                dataSource={[
                  { ...productLot, total_farms: traceData.total_farms },
                ]}
                columns={productLotColumns}
                pagination={false}
                size="small"
                bordered
                rowKey="product_lot_id"
              />
            </Card>

            {/* Farms Table */}
            <Card title="Danh sách Vườn">
              <Table
                dataSource={farms}
                columns={farmColumns}
                rowKey="plot_id"
                pagination={false}
                size="small"
                bordered
                expandable={{
                  expandedRowRender: (farm: ITraceFarm) => (
                    <Table
                      dataSource={farm.transaction_tickets}
                      rowKey="transaction_ticket_id"
                      pagination={false}
                      size="small"
                      columns={[
                        {
                          title: "Mã phiếu",
                          dataIndex: "transaction_ticket_code",
                          render: (text: string) => <Text strong>{text}</Text>,
                        },
                        {
                          title: "Người bán",
                          dataIndex: "seller_name",
                        },
                        {
                          title: "Người mua",
                          dataIndex: "buyer_name",
                        },
                        {
                          title: "Mủ nước (kg)",
                          dataIndex: "ticket_latex_weight",
                          align: "right" as const,
                          render: (val: number) => val.toLocaleString(),
                        },
                        {
                          title: "Mủ tạp (kg)",
                          dataIndex: "ticket_scrap_rubber_weight",
                          align: "right" as const,
                          render: (val: number) => val.toLocaleString(),
                        },
                        {
                          title: "Trạng thái",
                          dataIndex: "ticket_status",
                          render: (status: string) => (
                            <Tag
                              color={
                                status === "completed" ? "green" : "default"
                              }>
                              {status === "completed" ? "Hoàn thành" : status}
                            </Tag>
                          ),
                        },
                      ]}
                    />
                  ),
                }}
              />
            </Card>
          </Space>
        ) : searchCode && !isLoading ? (
          <Card>
            <Empty
              description={
                isError
                  ? "Không tìm thấy thông tin truy xuất"
                  : "Không có dữ liệu cho mã lô sản phẩm này"
              }
            />
          </Card>
        ) : (
          !searchCode && (
            <Card style={{ textAlign: "center", padding: "40px 0" }}>
              <Empty description="Nhập mã lô sản phẩm để bắt đầu tra cứu" />
            </Card>
          )
        )}
      </Spin>
    </>
  );
};

const ProductLotTracking = () => (
  <Suspense
    fallback={
      <Card style={{ textAlign: "center", padding: "40px 0" }}>
        <Spin description="Đang tải..." />
      </Card>
    }>
    <ProductLotTrackingContent />
  </Suspense>
);

export default ProductLotTracking;
