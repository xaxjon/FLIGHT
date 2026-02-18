import React, { useState, useEffect, useRef } from 'react';
import { 
  Plane, 
  AlertTriangle, 
  ShieldAlert, 
  Info, 
  Radio, 
  Database, 
  Search, 
  CheckCircle2, 
  XCircle, 
  Activity,
  Wind,
  Clock,
  Menu,
  Terminal,
  ChevronDown,
  ChevronUp,
  BrainCircuit,
  Key,
  ExternalLink
} from 'lucide-react';

export default function AeroIntelApp() {
  // STATE MANAGEMENT
  const [apiKey, setApiKey] = useState('');
  const [icao, setIcao] = useState('SAWH');
  const [notamText, setNotamText] = useState('');
  const [intelText, setIntelText] = useState('');
  const [loading, setLoading] = useState(false);
  const [briefing, setBriefing] = useState(null);
  const [error, setError] = useState(null);
  const [activeTab, setActiveTab] = useState('dashboard');
  
  // AI ANALYSIS ENGINE
  const generateBriefing = async () => {
    if (!apiKey) {
      setError("System Halted: Missing API Key. Please enter a valid Gemini API Key in the configuration panel.");
      return;
    }

    if (!notamText && !intelText) {
      setError("Please provide at least one source of data (NOTAMs or Intel).");
      return;
    }

    setLoading(true);
    setError(null);
    setBriefing(null);

    const systemPrompt = `You are SENTINEL, an advanced Aviation Risk Analysis AI. 
    Your goal is to synthesize Official NOTAM data and Unverified Raw Intel (News, Rumors, User Reports) into a cohesive pilot briefing.

    RULES:
    1. PRIORITIZE SAFETY. If sources conflict, assume the worst-case scenario until verified.
    2. ASSIGN RELIABILITY SCORES (0-100%):
       - Official NOTAMs = 90-100%
       - News Reports = 50-80%
       - Social Media/Rumors = 10-40%
    3. DETECT HALLUCINATIONS: If critical info (like runway status) is missing, state "UNKNOWN". Do not invent data.
    4. CATEGORIZE items into:
       - CRITICAL (Red): Closures, Strikes, Extreme Weather, Hazard.
       - WARNING (Amber): Equipment failure (ILS/PAPI), Delays, Moderate Weather.
       - INFO (Blue): General info, minor maintenance.
    
    OUTPUT FORMAT: JSON ONLY.
    Structure:
    {
      "summary": "Concise operational executive summary.",
      "overall_status": "GO" | "CAUTION" | "NO-GO",
      "weather_check": "Brief weather synthesis if present in data, else 'Not Provided'",
      "items": [
        {
          "id": "unique_id",
          "title": "Short Headline",
          "description": "Detailed explanation for the pilot.",
          "category": "CRITICAL" | "WARNING" | "INFO",
          "source": "NOTAM A0123/25" or "Local News",
          "reliability": 85,
          "validity": "Timeframe or 'Unknown'"
        }
      ]
    }`;

    const userPrompt = `ICAO: ${icao}
    
    === OFFICIAL NOTAM DATA ===
    ${notamText}

    === RAW UNVERIFIED INTEL ===
    ${intelText}`;

    try {
      const response = await fetch(
        `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-09-2025:generateContent?key=${apiKey}`,
        {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            contents: [{ parts: [{ text: userPrompt }] }],
            systemInstruction: { parts: [{ text: systemPrompt }] },
            generationConfig: { responseMimeType: "application/json" }
          }),
        }
      );

      if (!response.ok) {
        if (response.status === 400) throw new Error("API Key Invalid or API Error.");
        throw new Error(`API Error: ${response.status}`);
      }

      const data = await response.json();
      const resultText = data.candidates?.[0]?.content?.parts?.[0]?.text;
      
      if (!resultText) throw new Error("No analysis generated.");
      
      const parsedBriefing = JSON.parse(resultText);
      setBriefing(parsedBriefing);
      setActiveTab('dashboard');

    } catch (err) {
      console.error(err);
      setError("Analysis Failed: " + err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-950 text-slate-200 font-sans selection:bg-cyan-500/30">
      
      {/* HEADER */}
      <header className="bg-slate-900 border-b border-slate-800 sticky top-0 z-50 backdrop-blur-md bg-opacity-80">
        <div className="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-cyan-600 rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(8,145,178,0.5)]">
              <Plane className="text-white transform -rotate-45" size={24} />
            </div>
            <div>
              <h1 className="text-xl font-bold tracking-wider text-white">SENTINEL <span className="text-cyan-500">AI</span></h1>
              <p className="text-[10px] text-slate-500 tracking-[0.2em] font-mono uppercase">Unified Aviation Intelligence</p>
            </div>
          </div>
          <div className="flex items-center gap-4">
            <div className="hidden md:flex items-center gap-2 px-3 py-1 bg-slate-800 rounded-full border border-slate-700">
              <div className={`w-2 h-2 rounded-full ${apiKey ? 'bg-green-500 animate-pulse' : 'bg-red-500'}`}></div>
              <span className="text-xs font-mono text-slate-400">{apiKey ? 'SYSTEM ONLINE' : 'AUTH REQUIRED'}</span>
            </div>
          </div>
        </div>
      </header>

      <main className="max-w-7xl mx-auto p-4 lg:p-8 space-y-8">
        
        {/* INPUT SECTION */}
        <section className={`transition-all duration-500 ${briefing ? 'hidden' : 'block'}`}>
          <div className="mb-8 text-center space-y-2">
            <h2 className="text-3xl font-light text-white">Mission Data Ingest</h2>
            <p className="text-slate-400">Synthesize official data streams with raw open-source intelligence.</p>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            {/* LEFT COLUMN - CONFIG & ICAO */}
            <div className="lg:col-span-3 space-y-4">
              
              {/* API Key Input */}
              <div className="bg-slate-900 p-5 rounded-xl border border-slate-800 shadow-lg relative group">
                <label className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-2">
                  <Key size={12} /> System Access Key
                </label>
                <input 
                  type="password" 
                  value={apiKey}
                  onChange={(e) => setApiKey(e.target.value)}
                  placeholder="Enter Gemini API Key"
                  className="w-full bg-slate-950 border border-slate-700 text-sm font-mono text-white p-3 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none mb-2"
                />
                <a 
                  href="https://aistudio.google.com/app/apikey" 
                  target="_blank" 
                  rel="noopener noreferrer"
                  className="text-[10px] text-cyan-500 hover:text-cyan-400 flex items-center gap-1 hover:underline"
                >
                  <ExternalLink size={10} /> Get API Key (Google AI Studio)
                </a>
              </div>

              {/* ICAO Input */}
              <div className="bg-slate-900 p-5 rounded-xl border border-slate-800 shadow-lg">
                <label className="text-xs font-bold text-cyan-500 uppercase tracking-wider mb-2 block">Target Aerodrome</label>
                <div className="relative">
                  <input 
                    type="text" 
                    value={icao}
                    onChange={(e) => setIcao(e.target.value.toUpperCase())}
                    className="w-full bg-slate-950 border border-slate-700 text-3xl font-mono text-white p-4 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none text-center tracking-widest"
                    maxLength={4}
                  />
                  <Search className="absolute right-4 top-1/2 -translate-y-1/2 text-slate-600" size={20} />
                </div>
              </div>

            </div>

            {/* MIDDLE COLUMN - OFFICIAL DATA */}
            <div className="lg:col-span-4 flex flex-col gap-2">
               <div className="flex items-center justify-between">
                 <label className="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider">
                   <Database size={14} /> Official NOTAMs
                 </label>
                 <span className="text-[10px] bg-slate-800 px-2 py-0.5 rounded text-slate-500">ICAO FORMAT</span>
               </div>
               <textarea 
                 value={notamText}
                 onChange={(e) => setNotamText(e.target.value)}
                 placeholder="Paste raw NOTAM text here (e.g. A0123/25...)"
                 className="flex-1 bg-slate-900/50 border border-slate-700 rounded-xl p-4 font-mono text-xs text-green-500/90 focus:ring-2 focus:ring-green-900/50 outline-none resize-none min-h-[300px]"
               />
            </div>

            {/* RIGHT COLUMN - RAW INTEL */}
            <div className="lg:col-span-5 flex flex-col gap-2">
               <div className="flex items-center justify-between">
                 <label className="flex items-center gap-2 text-xs font-bold text-amber-500/80 uppercase tracking-wider">
                   <Radio size={14} /> Raw Intel / Scrapings
                 </label>
                 <span className="text-[10px] bg-amber-900/20 px-2 py-0.5 rounded text-amber-500/60">UNVERIFIED</span>
               </div>
               <textarea 
                 value={intelText}
                 onChange={(e) => setIntelText(e.target.value)}
                 placeholder="Paste news snippets, forum rumors, emails, or scraped web text here..."
                 className="flex-1 bg-slate-900/50 border border-slate-700 border-dashed rounded-xl p-4 font-mono text-xs text-amber-200/70 focus:ring-2 focus:ring-amber-900/50 outline-none resize-none min-h-[300px]"
               />
            </div>
          </div>

          {/* ACTION AREA */}
          <div className="mt-8 flex justify-center">
            <button 
              onClick={generateBriefing}
              disabled={loading}
              className={`
                group relative px-8 py-4 bg-cyan-600 hover:bg-cyan-500 rounded-full text-white font-bold tracking-widest uppercase transition-all shadow-[0_0_20px_rgba(8,145,178,0.4)] hover:shadow-[0_0_40px_rgba(8,145,178,0.6)] disabled:opacity-50 disabled:cursor-not-allowed overflow-hidden
              `}
            >
              <div className="flex items-center gap-3 relative z-10">
                {loading ? (
                  <>
                    <Activity className="animate-spin" />
                    Synthesizing Intelligence...
                  </>
                ) : (
                  <>
                    <BrainCircuit />
                    Synthesize Intel
                  </>
                )}
              </div>
              {!loading && <div className="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></div>}
            </button>
          </div>

          {error && (
            <div className="mt-6 mx-auto max-w-2xl bg-red-950/50 border border-red-900/50 p-4 rounded-lg flex items-center gap-3 text-red-200">
              <XCircle className="shrink-0" />
              <p>{error}</p>
            </div>
          )}
        </section>


        {/* RESULTS DASHBOARD */}
        {briefing && (
          <div className="animate-in fade-in slide-in-from-bottom-8 duration-700">
            
            {/* DASHBOARD HEADER */}
            <div className="flex flex-col md:flex-row gap-6 mb-8 items-start md:items-end justify-between border-b border-slate-800 pb-6">
              <div>
                <button onClick={() => setBriefing(null)} className="text-xs text-slate-500 hover:text-cyan-400 mb-2 flex items-center gap-1">
                  &larr; NEW QUERY
                </button>
                <h2 className="text-4xl font-bold text-white flex items-center gap-3">
                  {icao} <span className="text-slate-600 text-2xl font-light">ANALYSIS COMPLETE</span>
                </h2>
              </div>
              
              <div className={`
                px-6 py-2 rounded-full border flex items-center gap-3 shadow-lg
                ${briefing.overall_status === 'NO-GO' ? 'bg-red-950/50 border-red-500 text-red-500' : 
                  briefing.overall_status === 'CAUTION' ? 'bg-amber-950/50 border-amber-500 text-amber-500' : 
                  'bg-green-950/50 border-green-500 text-green-500'}
              `}>
                <div className={`w-3 h-3 rounded-full animate-pulse ${
                  briefing.overall_status === 'NO-GO' ? 'bg-red-500' : 
                  briefing.overall_status === 'CAUTION' ? 'bg-amber-500' : 
                  'bg-green-500'
                }`}></div>
                <span className="font-bold tracking-widest text-lg">{briefing.overall_status}</span>
              </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
              
              {/* SUMMARY CARD */}
              <div className="lg:col-span-3 bg-gradient-to-r from-slate-900 to-slate-800 border border-slate-700 rounded-2xl p-6 shadow-xl relative overflow-hidden">
                <div className="absolute top-0 right-0 p-4 opacity-10">
                  <Plane size={120} />
                </div>
                <h3 className="text-xs font-bold text-cyan-500 uppercase tracking-widest mb-3 flex items-center gap-2">
                  <Activity size={14} /> Mission Summary
                </h3>
                <p className="text-lg md:text-xl text-slate-200 font-light leading-relaxed max-w-4xl">
                  {briefing.summary}
                </p>
                {briefing.weather_check !== 'Not Provided' && (
                  <div className="mt-4 flex items-start gap-2 text-sm text-slate-400 bg-slate-950/50 p-3 rounded-lg inline-flex">
                    <Wind size={16} className="mt-0.5 text-slate-500" />
                    <span>{briefing.weather_check}</span>
                  </div>
                )}
              </div>

              {/* CRITICAL COLUMN */}
              <div className="lg:col-span-2 space-y-4">
                <div className="flex items-center gap-2 mb-2">
                   <ShieldAlert className="text-red-500" size={18} />
                   <h3 className="text-sm font-bold text-slate-400 uppercase tracking-wider">Critical Hazards</h3>
                </div>
                
                {briefing.items.filter(i => i.category === 'CRITICAL').length === 0 ? (
                   <div className="p-6 border border-slate-800 border-dashed rounded-xl text-center text-slate-600">
                     No Critical Hazards Detected
                   </div>
                ) : (
                  briefing.items.filter(i => i.category === 'CRITICAL').map((item) => (
                    <RiskCard key={item.id} item={item} />
                  ))
                )}

                <div className="flex items-center gap-2 mt-8 mb-2">
                   <AlertTriangle className="text-amber-500" size={18} />
                   <h3 className="text-sm font-bold text-slate-400 uppercase tracking-wider">Operational Advisories</h3>
                </div>

                {briefing.items.filter(i => i.category === 'WARNING').map((item) => (
                    <RiskCard key={item.id} item={item} />
                ))}
              </div>

              {/* INFO & STATS COLUMN */}
              <div className="space-y-6">
                
                {/* INFO LIST */}
                <div className="bg-slate-900 rounded-xl border border-slate-800 p-5">
                   <h3 className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                     <Info size={14} /> General Info
                   </h3>
                   <div className="space-y-4">
                     {briefing.items.filter(i => i.category === 'INFO').map((item) => (
                       <div key={item.id} className="text-sm border-l-2 border-slate-700 pl-3">
                         <h4 className="text-slate-300 font-medium">{item.title}</h4>
                         <p className="text-slate-500 text-xs mt-1">{item.description}</p>
                       </div>
                     ))}
                     {briefing.items.filter(i => i.category === 'INFO').length === 0 && (
                       <p className="text-slate-600 text-xs italic">No general info items.</p>
                     )}
                   </div>
                </div>

                {/* LEGEND / HELP */}
                <div className="bg-slate-950 rounded-xl border border-slate-800 p-5 opacity-70">
                  <h4 className="text-[10px] font-bold text-slate-600 uppercase mb-2">Reliability Matrix</h4>
                  <div className="space-y-2 text-[10px] text-slate-500 font-mono">
                    <div className="flex items-center justify-between">
                      <span className="flex items-center gap-2"><span className="w-2 h-2 rounded-full bg-cyan-500"></span> OFFICIAL SRC</span>
                      <span>90-100%</span>
                    </div>
                    <div className="flex items-center justify-between">
                      <span className="flex items-center gap-2"><span className="w-2 h-2 rounded-full bg-yellow-500"></span> MEDIA/NEWS</span>
                      <span>50-80%</span>
                    </div>
                    <div className="flex items-center justify-between">
                      <span className="flex items-center gap-2"><span className="w-2 h-2 rounded-full bg-red-500"></span> UNVERIFIED</span>
                      <span>10-40%</span>
                    </div>
                  </div>
                </div>

              </div>

            </div>
          </div>
        )}

      </main>
    </div>
  );
}

// SUB-COMPONENT FOR RISK CARDS
function RiskCard({ item }) {
  const [expanded, setExpanded] = useState(false);

  // Dynamic Styles based on category
  const styles = {
    CRITICAL: "bg-red-950/20 border-red-500/30 hover:border-red-500/60 text-red-100",
    WARNING: "bg-amber-950/20 border-amber-500/30 hover:border-amber-500/60 text-amber-100",
    INFO: "bg-slate-800 border-slate-700 text-slate-300"
  };

  const reliabilityColor = (score) => {
    if (score >= 90) return "text-cyan-400";
    if (score >= 50) return "text-yellow-400";
    return "text-red-400";
  };

  return (
    <div className={`border rounded-xl p-4 transition-all duration-300 ${styles[item.category]}`}>
      <div className="flex items-start justify-between cursor-pointer" onClick={() => setExpanded(!expanded)}>
        <div className="flex-1">
          <div className="flex items-center gap-3 mb-1">
            <h4 className="font-bold text-base tracking-wide">{item.title}</h4>
            {item.category === 'CRITICAL' && <span className="text-[10px] bg-red-500/20 text-red-500 px-2 rounded font-bold uppercase">No-Go</span>}
          </div>
          <p className={`text-sm opacity-80 ${expanded ? '' : 'line-clamp-1'}`}>{item.description}</p>
        </div>
        <div className="ml-4 flex flex-col items-end gap-1">
           <div className={`text-[10px] font-mono flex items-center gap-1 ${reliabilityColor(item.reliability)}`}>
             {item.reliability}% REL
             <div className="w-12 h-1 bg-slate-700 rounded-full overflow-hidden">
                <div className="h-full bg-current" style={{ width: `${item.reliability}%` }}></div>
             </div>
           </div>
           {expanded ? <ChevronUp size={16} className="opacity-50" /> : <ChevronDown size={16} className="opacity-50" />}
        </div>
      </div>
      
      {/* EXPANDED DETAILS */}
      <div className={`grid transition-all duration-300 overflow-hidden ${expanded ? 'grid-rows-[1fr] mt-4 pt-4 border-t border-white/10' : 'grid-rows-[0fr]'}`}>
        <div className="min-h-0 text-xs font-mono opacity-70 space-y-2">
          <div className="grid grid-cols-2 gap-4">
            <div>
               <span className="block text-[10px] uppercase opacity-50 mb-0.5">Source</span>
               <span className="flex items-center gap-1"><Database size={10} /> {item.source}</span>
            </div>
            <div>
               <span className="block text-[10px] uppercase opacity-50 mb-0.5">Validity</span>
               <span className="flex items-center gap-1"><Clock size={10} /> {item.validity}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}