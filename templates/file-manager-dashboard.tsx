import React, { useState } from 'react';
import { Search, Menu, MoreHorizontal, Folder, Image, Video, FileText, Clock, Calendar, Mic, MessageSquare, Settings, Users } from 'lucide-react';

const FileManagerDashboard = () => {
  const [selectedDate] = useState('Jan 7, 2021');
  
  const storageData = [
    { day: 'Mon', value: 160, files: 2200 },
    { day: 'Tue', value: 145, files: 2514 },
    { day: 'Wed', value: 170, files: 2954 },
    { day: 'Thu', value: 140, files: 2100 },
    { day: 'Fri', value: 165, files: 2800 },
    { day: 'Sat', value: 155, files: 2600 },
    { day: 'Sun', value: 180, files: 3200 }
  ];

  const categoryCards = [
    { name: 'Photos', count: 4524, usage: 25, color: 'from-purple-500 to-pink-500', lastAccess: '5 Days ago' },
    { name: 'Photos', count: 250, usage: 75, color: 'from-orange-500 to-red-500', lastAccess: '1 Week before' },
    { name: 'Photos', count: 250, usage: 45, color: 'from-red-500 to-pink-500', lastAccess: '1 Week before' }
  ];

  const favoriteDocuments = [
    { name: 'Products', files: 150, icon: Folder, color: 'bg-red-500' },
    { name: 'Web Design', files: 150, icon: Folder, color: 'bg-orange-500' },
    { name: 'Photos', files: 1540, icon: Folder, color: 'bg-purple-500' }
  ];

  const sidebarItems = [
    { icon: Menu, active: false },
    { icon: Clock, active: false },
    { icon: Calendar, active: false },
    { icon: Mic, active: false },
    { icon: MessageSquare, active: false },
    { icon: Settings, active: false },
    { icon: Users, active: false }
  ];

  const StorageChart = () => {
    const maxValue = Math.max(...storageData.map(d => d.value));
    
    return (
      <div className="bg-gray-800 rounded-3xl p-6 col-span-2">
        <div className="h-64 relative">
          <div className="absolute inset-0 flex items-end justify-between">
            {/* Y-axis labels */}
            <div className="flex flex-col justify-between h-full text-xs text-gray-400 mr-4">
              <span>180 GB</span>
              <span>160 GB</span>
              <span>140 GB</span>
              <span>110 GB</span>
            </div>
            
            {/* Chart area */}
            <div className="flex-1 h-full relative">
              <svg className="w-full h-full" viewBox="0 0 400 200">
                {/* Grid lines */}
                {[0, 1, 2, 3, 4].map(i => (
                  <line 
                    key={i} 
                    x1="0" 
                    y1={i * 40} 
                    x2="400" 
                    y2={i * 40} 
                    stroke="#374151" 
                    strokeDasharray="2,2" 
                    strokeWidth="1"
                  />
                ))}
                
                {/* Chart line */}
                <path
                  d={`M 0 ${200 - (storageData[0].value / maxValue) * 160} ${storageData.map((d, i) => 
                    `L ${(i * 400) / (storageData.length - 1)} ${200 - (d.value / maxValue) * 160}`
                  ).join(' ')}`}
                  fill="none"
                  stroke="url(#gradient)"
                  strokeWidth="3"
                />
                
                {/* Data points */}
                {storageData.map((d, i) => (
                  <circle
                    key={i}
                    cx={(i * 400) / (storageData.length - 1)}
                    cy={200 - (d.value / maxValue) * 160}
                    r="6"
                    fill="#8B5CF6"
                    stroke="#1F2937"
                    strokeWidth="3"
                  />
                ))}
                
                {/* Gradient definition */}
                <defs>
                  <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stopColor="#8B5CF6" />
                    <stop offset="100%" stopColor="#EC4899" />
                  </linearGradient>
                </defs>
              </svg>
              
              {/* Value labels */}
              {storageData.map((d, i) => (
                <div 
                  key={i}
                  className="absolute -top-8 text-xs text-white bg-purple-600 px-2 py-1 rounded"
                  style={{ 
                    left: `${(i * 100) / (storageData.length - 1)}%`,
                    transform: 'translateX(-50%)'
                  }}
                >
                  {d.files}
                </div>
              ))}
            </div>
          </div>
        </div>
        
        {/* X-axis labels */}
        <div className="flex justify-between mt-4 text-sm text-gray-400">
          {storageData.map((d, i) => (
            <span key={i}>{d.day}</span>
          ))}
        </div>
      </div>
    );
  };

  const CircularProgress = ({ percentage, size = 120 }) => {
    const radius = 45;
    const strokeWidth = 8;
    const circumference = 2 * Math.PI * radius;
    const strokeDashoffset = circumference - (percentage / 100) * circumference;

    return (
      <div className="relative" style={{ width: size, height: size }}>
        <svg className="transform -rotate-90" width={size} height={size}>
          <circle
            cx={size / 2}
            cy={size / 2}
            r={radius}
            stroke="#374151"
            strokeWidth={strokeWidth}
            fill="none"
          />
          <circle
            cx={size / 2}
            cy={size / 2}
            r={radius}
            stroke="url(#progressGradient)"
            strokeWidth={strokeWidth}
            fill="none"
            strokeDasharray={circumference}
            strokeDashoffset={strokeDashoffset}
            strokeLinecap="round"
          />
          <defs>
            <linearGradient id="progressGradient" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stopColor="#8B5CF6" />
              <stop offset="100%" stopColor="#EC4899" />
            </linearGradient>
          </defs>
        </svg>
        <div className="absolute inset-0 flex items-center justify-center flex-col">
          <span className="text-2xl font-bold text-white">{percentage}%</span>
          <span className="text-sm text-gray-400">used</span>
        </div>
      </div>
    );
  };

  return (
    <div className="min-h-screen bg-gray-900 text-white">
      {/* Header */}
      <div className="bg-black/20 backdrop-blur-sm p-4">
        <div className="flex items-center justify-between max-w-7xl mx-auto">
          <div className="flex items-center space-x-4">
            <h1 className="text-2xl font-bold">File Manager Dashboard</h1>
            <p className="text-gray-400">Move and manage files, review and modify them quickly</p>
          </div>
        </div>
      </div>

      <div className="flex max-w-7xl mx-auto">
        {/* Sidebar */}
        <div className="w-20 bg-gradient-to-b from-teal-500 to-gray-700 rounded-r-3xl m-4 p-4 flex flex-col items-center space-y-6">
          <div className="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
            <Menu className="w-5 h-5" />
          </div>
          {sidebarItems.map((item, index) => (
            <div key={index} className="w-8 h-8 flex items-center justify-center hover:bg-white/10 rounded-lg transition-colors">
              <item.icon className="w-5 h-5 text-white/70" />
            </div>
          ))}
          <div className="mt-auto text-xs text-white/50">v1.2.3</div>
        </div>

        {/* Main Content */}
        <div className="flex-1 p-6">
          {/* Top Section */}
          <div className="flex items-center justify-between mb-8">
            <div>
              <h2 className="text-xl font-semibold mb-1">My Documents</h2>
              <p className="text-gray-400">Photos, Videos, Documents</p>
            </div>
            <div className="flex items-center space-x-4">
              <div className="relative">
                <Search className="absolute left-3 top-3 w-4 h-4 text-gray-400" />
                <input 
                  type="text" 
                  placeholder="Search"
                  className="bg-gray-800 rounded-full pl-10 pr-4 py-2 w-64 focus:outline-none focus:ring-2 focus:ring-purple-500"
                />
              </div>
              <div className="flex items-center space-x-2">
                <span className="text-sm text-gray-400">{selectedDate}</span>
                <div className="w-8 h-8 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center">
                  <span className="text-xs font-bold">JD</span>
                </div>
              </div>
            </div>
          </div>

          {/* Category Cards */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            {categoryCards.map((card, index) => (
              <div key={index} className="bg-gray-800 rounded-3xl p-6 relative overflow-hidden">
                <div className={`absolute inset-0 bg-gradient-to-r ${card.color} opacity-20`}></div>
                <div className="relative z-10">
                  <div className="flex items-center justify-between mb-4">
                    <div className={`w-12 h-12 rounded-xl bg-gradient-to-r ${card.color} flex items-center justify-center`}>
                      <Image className="w-6 h-6" />
                    </div>
                    <MoreHorizontal className="w-5 h-5 text-gray-400" />
                  </div>
                  <h3 className="text-lg font-semibold mb-1">{card.name}</h3>
                  <p className="text-gray-400 text-sm mb-4">{card.count.toLocaleString()}</p>
                  <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-2">
                      <span className="text-sm font-medium">{card.usage}%</span>
                      <div className="w-16 h-2 bg-gray-700 rounded-full overflow-hidden">
                        <div 
                          className={`h-full bg-gradient-to-r ${card.color} transition-all duration-300`}
                          style={{ width: `${card.usage}%` }}
                        ></div>
                      </div>
                    </div>
                    <span className="text-xs text-gray-400">{card.lastAccess}</span>
                  </div>
                </div>
              </div>
            ))}
          </div>

          {/* Storage Chart and Usage */}
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <StorageChart />
            
            {/* Storage Usage */}
            <div className="bg-gradient-to-br from-purple-600 to-pink-600 rounded-3xl p-6 flex flex-col items-center justify-center">
              <CircularProgress percentage={65} />
              <div className="mt-4 text-center">
                <div className="grid grid-cols-2 gap-4 mt-4">
                  <div>
                    <p className="text-sm opacity-80">Total Space</p>
                    <p className="font-bold">256 GB</p>
                  </div>
                  <div>
                    <p className="text-sm opacity-80">Used Space</p>
                    <p className="font-bold">180 GB</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Favorite Documents */}
          <div className="mb-8">
            <h3 className="text-lg font-semibold mb-4">Favorite Documents</h3>
            <div className="space-y-4">
              {favoriteDocuments.map((doc, index) => (
                <div key={index} className="bg-gray-800 rounded-2xl p-4 flex items-center justify-between hover:bg-gray-700 transition-colors">
                  <div className="flex items-center space-x-4">
                    <div className={`w-10 h-10 ${doc.color} rounded-xl flex items-center justify-center`}>
                      <doc.icon className="w-5 h-5" />
                    </div>
                    <div>
                      <h4 className="font-medium">{doc.name}</h4>
                      <p className="text-sm text-gray-400">{doc.files} files</p>
                    </div>
                  </div>
                  <MoreHorizontal className="w-5 h-5 text-gray-400" />
                </div>
              ))}
            </div>
          </div>

          {/* My Favorite Section */}
          <div>
            <h3 className="text-lg font-semibold mb-4">My Favorite</h3>
            <p className="text-gray-400 mb-4">Photos, Videos, Documents</p>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="bg-gray-800 rounded-3xl p-6">
                <div className="flex items-center justify-between mb-4">
                  <div className="flex items-center space-x-3">
                    <div className="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl flex items-center justify-center">
                      <Image className="w-5 h-5" />
                    </div>
                    <div>
                      <h4 className="font-medium">Photos</h4>
                      <p className="text-sm text-gray-400">4,524 Files</p>
                    </div>
                  </div>
                  <div className="flex -space-x-2">
                    {[1,2,3,4,5].map(i => (
                      <div key={i} className="w-8 h-8 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full border-2 border-gray-800"></div>
                    ))}
                  </div>
                </div>
              </div>
              
              <div className="bg-gray-800 rounded-3xl p-6">
                <div className="flex items-center justify-between mb-4">
                  <div className="flex items-center space-x-3">
                    <div className="w-10 h-10 bg-gradient-to-r from-orange-500 to-red-500 rounded-xl flex items-center justify-center">
                      <Video className="w-5 h-5" />
                    </div>
                    <div>
                      <h4 className="font-medium">Videos</h4>
                      <p className="text-sm text-gray-400">4,524 Files</p>
                    </div>
                  </div>
                  <div className="flex -space-x-2">
                    {[1,2,3,4,5].map(i => (
                      <div key={i} className="w-8 h-8 bg-gradient-to-r from-orange-500 to-red-500 rounded-full border-2 border-gray-800"></div>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default FileManagerDashboard;