import { useEffect, useRef, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { getProvince } from "@/lib/api";
import { IProvince } from "@/types/province";

interface ProvinceAutocompleteProps {
  value: string;
  onChange: (value: string) => void;
}

const ProvinceAutocomplete: React.FC<ProvinceAutocompleteProps> = ({
  value,
  onChange,
}) => {
  const [search, setSearch] = useState("");
  const [isOpen, setIsOpen] = useState(false);
  const wrapperRef = useRef<HTMLDivElement>(null);

  const { data, isLoading } = useQuery({
    queryKey: ["provinces"],
    queryFn: getProvince,
    refetchOnWindowFocus: false,
  });

  const provinces: IProvince[] = data?.provinces || [];

  const filteredProvinces = provinces.filter((province) =>
    province.province_name.toLowerCase().includes(search.toLowerCase())
  );

  const selectedProvince = provinces.find(
    (p) => p.province_id.toString() === value
  );

  const handleSelect = (province: IProvince) => {
    onChange(province.province_id.toString());
    setIsOpen(false);
    setSearch("");
  };

  const handleClickOutside = (e: MouseEvent) => {
    if (wrapperRef.current && !wrapperRef.current.contains(e.target as Node)) {
      setIsOpen(false);
    }
  };

  useEffect(() => {
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  return (
    <div className="relative w-full" ref={wrapperRef}>
      <input
        type="text"
        placeholder="Chọn tỉnh thành..."
        value={isOpen ? search : selectedProvince?.province_name || ""}
        onFocus={() => setIsOpen(true)}
        onChange={(e) => {
          setSearch(e.target.value);
          setIsOpen(true);
        }}
        className="!h-[36px] w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500"
      />
      {isOpen && (
        <ul className="absolute z-10 mt-1 w-full max-h-60 overflow-auto bg-white border rounded-md shadow-lg">
          {isLoading ? (
            <li className="px-4 py-2 text-gray-500">Đang tải...</li>
          ) : filteredProvinces.length === 0 ? (
            <li className="px-4 py-2 text-gray-500">
              Không tìm thấy tỉnh thành
            </li>
          ) : (
            filteredProvinces.map((province) => (
              <li
                key={province.province_id}
                onClick={() => handleSelect(province)}
                className={`px-4 py-2 cursor-pointer hover:bg-green-100 ${
                  province.province_id.toString() === value
                    ? "bg-green-200 font-medium"
                    : ""
                }`}
              >
                {province.province_name}
              </li>
            ))
          )}
        </ul>
      )}
    </div>
  );
};

export default ProvinceAutocomplete;
