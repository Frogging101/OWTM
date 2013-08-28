#   Copyright (C) 2013 OHRI 
#
#   This file is part of OWTG.
#
#   OWTG is free software: you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation, either version 3 of the License, or
#   (at your option) any later version.
#
#   OWTG is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with OWTG.  If not, see <http://www.gnu.org/licenses/>.

import os

etcDir = '/opt/owtg/etc/'
sFilename = etcDir+'sensors'
adbFilename = etcDir+'archive.rrd'
gdbFilename = etcDir+'graphing.rrd'
owtgDatPath = etcDir+'owtg.dat'

def getLines(filename):
    file_ = open(filename,'r') #Open file for reading
    lineList = file_.readlines()
    lineList_ = []
    file_.close()
    
    #Ignore comments (lines beginning with #)
    for line in lineList:
        if line.startswith('#'):
            continue
        if line:
            #Strip newlines
            line = line.rstrip('\n')
            lineList_.append(line)
    return lineList_            

class OWTGSensor:
    address = ''
    alias = ''
    minAlarm = 0.0
    maxAlarm = 0.0
    graph = False

def getSensors():
    dSensors = [] #Already discovered sensors
    
    lineList = getLines(sFilename)

    for line in lineList:
        if line:
            params = line.split(':')
            newSensor = OWTGSensor()
            newSensor.alias = params[0]
            newSensor.address = params[1]
            newSensor.minAlarm = float(params[4])
            newSensor.maxAlarm = float(params[5])
            if params[3] == 'y':
                newSensor.graph = True
            dSensors.append(newSensor)
    return dSensors
    
def dbExists():
    exists = True
    if not os.path.exists(adbFilename) or not os.path.exists(gdbFilename):
        exists = False
    return exists

def datCreate():
    owtgDat = open(owtgDatPath,'w+')
    owtgDat.writelines(['allowRun=\x80=1\n','width=\x80=\n','email=\x80='])
    owtgDat.close()

def datGetDictList():
    if not os.path.exists(owtgDatPath):
        datCreate()
    dicts = []
    lineList = getLines(owtgDatPath)
    for l in lineList:
        dicts.append({'param':l.split('=\x80=')[0],'value':l.split('=\x80=')[1]})
    return dicts
    
def datGet(param):
    dicts = datGetDictList()
    for d in dicts:
        if d['param'] == param:
            return d['value']

def datSet(param,value):
    dicts = datGetDictList()
    datOutput = []
    
    for d in dicts:
        if d['param'] == param:
            d['value'] = value
        datOutput.append(d['param']+'=\x80='+d['value']+'\n')
    owtgDat = open(owtgDatPath,'w')
    owtgDat.writelines(datOutput)
    owtgDat.close()
